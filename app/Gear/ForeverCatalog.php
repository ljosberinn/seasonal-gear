<?php

namespace App\Gear;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Read side of the dataset written by `forever:import`.
 *
 * @phpstan-import-type ForeverItem from ForeverResolver
 */
class ForeverCatalog
{
    private const int ITEM_CLASS_WEAPON = 2;

    private const int ARMOR_SUBCLASS_SHIELD = 6;

    /** Miscellaneous weapons and fishing poles need no weapon skill. */
    private const array UNRESTRICTED_WEAPON_SUBCLASSES = [14, 20];

    /** @var array{build: string, ratingsPerPercent: array<string, float>, items: list<ForeverItem>}|null */
    private ?array $dataset = null;

    /** @var array<string, array{mask: int, armor: int, shield: bool, weapons: array<int, true>}>|null */
    private ?array $proficiencies = null;

    public function __construct(private readonly string $path) {}

    public function build(): string
    {
        return $this->dataset()['build'];
    }

    /**
     * @return array<string, float>
     */
    public function ratingsPerPercent(): array
    {
        return $this->dataset()['ratingsPerPercent'];
    }

    /**
     * @return list<ForeverItem> grouped by slot in slot order, highest item level first within a slot
     */
    public function search(ForeverFilter $filter): array
    {
        $slotOrder = array_flip(array_column(Slot::cases(), 'value'));
        $results = array_values(array_filter($this->dataset()['items'], fn (array $item): bool => $this->matchesSlot($item, $filter)
            && $this->matchesLevels($item, $filter)
            && $this->hasStats($item, $filter->stats)
            && $this->matchesClass($item, $filter->class)));

        usort($results, fn (array $a, array $b): int => [$slotOrder[$a['slot']], $b['itemLevel'], $a['name']]
            <=> [$slotOrder[$b['slot']], $a['itemLevel'], $b['name']]);

        return $results;
    }

    /**
     * Result count per filter option, each counted against the other groups' active filters so it
     * previews what picking that option yields. Stats are AND-ed, so a stat counts items that also
     * have every already picked stat.
     *
     * @return array{slots: array<string, int>, stats: array<string, int>, anySlot: int, anyStat: int}
     */
    public function counts(ForeverFilter $filter): array
    {
        $counts = [
            'slots' => array_fill_keys(array_column(Slot::cases(), 'value'), 0),
            'stats' => array_fill_keys(ForeverStat::keys(), 0),
            'anySlot' => 0,
            'anyStat' => 0,
        ];

        foreach ($this->dataset()['items'] as $item) {
            if (! $this->matchesLevels($item, $filter)) {
                continue;
            }

            $matchesClass = $this->matchesClass($item, $filter->class);
            $matchesSlot = $this->matchesSlot($item, $filter);
            $matchesStats = $this->hasStats($item, $filter->stats);

            if ($matchesClass && $matchesStats) {
                $counts['anySlot']++;
                $counts['slots'][$item['slot']]++;
            }

            if (! $matchesClass || ! $matchesSlot) {
                continue;
            }

            $counts['anyStat']++;

            if ($matchesStats) {
                foreach ($item['stats'] as $stat => $value) {
                    if ($value > 0) {
                        $counts['stats'][$stat]++;
                    }
                }
            }
        }

        return $counts;
    }

    /**
     * Whether the class can equip the item, limited to its best armor type the way the retail list is.
     *
     * @param  ForeverItem  $item
     */
    public function isUsableBy(array $item, string $class): bool
    {
        $proficiencies = $this->proficiencies()[$class];
        $allowableClass = $item['allowableClass'];

        if ($allowableClass > 0 && ($allowableClass & $proficiencies['mask']) === 0) {
            return false;
        }

        if ($item['classId'] === self::ITEM_CLASS_WEAPON) {
            return isset($proficiencies['weapons'][$item['subclassId']]);
        }

        if ($item['subclassId'] === self::ARMOR_SUBCLASS_SHIELD) {
            return $proficiencies['shield'];
        }

        return ! Slot::from($item['slot'])->isArmor() || $item['subclassId'] === $proficiencies['armor'];
    }

    /**
     * Resolved once, since filtering and counting run this for every item.
     *
     * @return array<string, array{mask: int, armor: int, shield: bool, weapons: array<int, true>}>
     */
    private function proficiencies(): array
    {
        if ($this->proficiencies !== null) {
            return $this->proficiencies;
        }

        $this->proficiencies = [];

        foreach (config('forever.classes') as $key => $class) {
            $this->proficiencies[$key] = [
                'mask' => 1 << (config("classes.{$key}.id") - 1),
                'armor' => $class['armor'],
                'shield' => $class['shield'],
                'weapons' => array_fill_keys([...$class['weapons'], ...self::UNRESTRICTED_WEAPON_SUBCLASSES], true),
            ];
        }

        return $this->proficiencies;
    }

    /**
     * @param  ForeverItem  $item
     */
    private function matchesClass(array $item, ?string $class): bool
    {
        return $class === null || $this->isUsableBy($item, $class);
    }

    /**
     * @param  ForeverItem  $item
     */
    private function matchesSlot(array $item, ForeverFilter $filter): bool
    {
        return $filter->slot === null || $item['slot'] === $filter->slot->value;
    }

    /**
     * @param  ForeverItem  $item
     */
    private function matchesLevels(array $item, ForeverFilter $filter): bool
    {
        // Items without a requirement are wearable from level 1.
        $level = max(1, $item['requiredLevel']);

        return ($filter->minLevel === null || $level >= $filter->minLevel)
            && ($filter->maxLevel === null || $level <= $filter->maxLevel)
            && ($filter->minItemLevel === null || $item['itemLevel'] >= $filter->minItemLevel)
            && ($filter->maxItemLevel === null || $item['itemLevel'] <= $filter->maxItemLevel);
    }

    /**
     * Penalties such as Fletcher's Gloves' negative Parry don't count as having the stat.
     *
     * @param  ForeverItem  $item
     * @param  list<string>  $stats
     */
    private function hasStats(array $item, array $stats): bool
    {
        foreach ($stats as $stat) {
            if (($item['stats'][$stat] ?? 0) <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{build: string, ratingsPerPercent: array<string, float>, items: list<ForeverItem>}
     */
    private function dataset(): array
    {
        if ($this->dataset !== null) {
            return $this->dataset;
        }

        if (! File::exists($this->path)) {
            throw new RuntimeException("Forever dataset missing at {$this->path}; run `php artisan forever:import`.");
        }

        return $this->dataset = File::json($this->path, JSON_THROW_ON_ERROR);
    }
}
