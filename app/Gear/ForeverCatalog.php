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

    /** @var array<string, list<array{value: string, label: string}>>|null */
    private ?array $effectOptions = null;

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
     * @return list<ForeverItem> grouped by slot in slot order, highest item level first within a slot,
     *                           or highest value first for a picked effect that has one
     */
    public function search(ForeverFilter $filter): array
    {
        $slotOrder = array_flip(array_column(Slot::cases(), 'value'));
        $results = array_values(array_filter($this->dataset()['items'], fn (array $item): bool => $this->matchesSlot($item, $filter)
            && $this->matchesLevels($item, $filter)
            && $this->hasStats($item, $filter->stats)
            && $this->matchesEffect($item, $filter)
            && $this->matchesClass($item, $filter->class)));
        $effectValue = fn (array $item): float => $filter->effect === null ? 0 : (float) ($item['effectCategories'][$filter->effect] ?? 0);

        usort($results, fn (array $a, array $b): int => [$slotOrder[$a['slot']], $effectValue($b), $b['itemLevel'], $a['name']]
            <=> [$slotOrder[$b['slot']], $effectValue($a), $a['itemLevel'], $b['name']]);

        return $results;
    }

    /**
     * Effect filter choices by trigger label: each category the dataset has, followed by one
     * "at least" choice per higher value for categories with a unit.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    public function effectOptions(): array
    {
        if ($this->effectOptions !== null) {
            return $this->effectOptions;
        }

        $values = [];

        foreach ($this->dataset()['items'] as $item) {
            foreach ($item['effectCategories'] as $key => $value) {
                $values[$key] ??= [];

                if ($value !== null) {
                    $values[$key][self::formatEffectValue($value)] = $value;
                }
            }
        }

        $categories = array_filter(array_map(ForeverEffectClassifier::parseKey(...), array_combine(array_keys($values), array_keys($values))));
        $triggerOrder = array_flip(array_column(ForeverEffectTrigger::cases(), 'value'));
        $outcomeOrder = array_flip(array_column(ForeverEffectOutcome::cases(), 'value'));
        uasort($categories, fn (array $a, array $b): int => [$triggerOrder[$a[0]->value], $outcomeOrder[$a[1]->value]]
            <=> [$triggerOrder[$b[0]->value], $outcomeOrder[$b[1]->value]]);
        $this->effectOptions = [];

        foreach ($categories as $key => [$trigger, $outcome]) {
            $this->effectOptions[$trigger->label()][] = ['value' => $key, 'label' => $outcome->label()];
            asort($values[$key]);

            // The lowest value would match the same items as the category itself.
            foreach (array_slice(array_keys($values[$key]), 1) as $value) {
                $this->effectOptions[$trigger->label()][] = ['value' => "{$key}:{$value}", 'label' => "{$outcome->label()} ≥ {$value}{$outcome->unit()}"];
            }
        }

        return $this->effectOptions;
    }

    /**
     * @return list<string>
     */
    public function effectOptionValues(): array
    {
        return array_column(array_merge(...array_values($this->effectOptions())), 'value');
    }

    public static function formatEffectValue(float $value): string
    {
        return rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    /**
     * Result count per filter option, each counted against the other groups' active filters so it
     * previews what picking that option yields. Stats are AND-ed, so a stat counts items that also
     * have every already picked stat.
     *
     * @return array{slots: array<string, int>, stats: array<string, int>, effects: array<string, int>, anySlot: int, anyStat: int, anyEffect: int}
     */
    public function counts(ForeverFilter $filter): array
    {
        $counts = [
            'slots' => array_fill_keys(array_column(Slot::cases(), 'value'), 0),
            'stats' => array_fill_keys(ForeverStat::keys(), 0),
            'effects' => array_fill_keys($this->effectOptionValues(), 0),
            'anySlot' => 0,
            'anyStat' => 0,
            'anyEffect' => 0,
        ];
        $thresholds = [];

        foreach ($this->effectOptionValues() as $option) {
            if (str_contains($option, ':')) {
                [$key, $value] = explode(':', $option);
                $thresholds[$key][$option] = (float) $value;
            }
        }

        foreach ($this->dataset()['items'] as $item) {
            if (! $this->matchesLevels($item, $filter) || ! $this->matchesClass($item, $filter->class)) {
                continue;
            }

            $matchesSlot = $this->matchesSlot($item, $filter);
            $matchesStats = $this->hasStats($item, $filter->stats);
            $matchesEffect = $this->matchesEffect($item, $filter);

            if ($matchesStats && $matchesEffect) {
                $counts['anySlot']++;
                $counts['slots'][$item['slot']]++;
            }

            if (! $matchesSlot) {
                continue;
            }

            if ($matchesEffect) {
                $counts['anyStat']++;

                if ($matchesStats) {
                    foreach ($item['stats'] as $stat => $value) {
                        if ($value > 0) {
                            $counts['stats'][$stat]++;
                        }
                    }
                }
            }

            if ($matchesStats) {
                $counts['anyEffect']++;

                foreach ($item['effectCategories'] as $key => $value) {
                    $counts['effects'][$key]++;

                    foreach ($thresholds[$key] ?? [] as $option => $threshold) {
                        if ($value >= $threshold) {
                            $counts['effects'][$option]++;
                        }
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
     * @param  ForeverItem  $item
     */
    private function matchesEffect(array $item, ForeverFilter $filter): bool
    {
        if ($filter->effect === null) {
            return true;
        }

        if (! array_key_exists($filter->effect, $item['effectCategories'])) {
            return false;
        }

        return $filter->minEffectValue === null || ($item['effectCategories'][$filter->effect] ?? 0) >= $filter->minEffectValue;
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
