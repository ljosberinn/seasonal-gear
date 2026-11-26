<?php

namespace App\Gear;

use RuntimeException;

/**
 * Read side of the dataset written by `gear:import`.
 */
class GearCatalog
{
    /**
     * Source types the site shows. `gear:import` still collects catalyst items, but they're left out
     * here until the catalyst is usable this season.
     */
    public const array SOURCE_TYPES = [
        'raid' => 'Raid',
        'tier' => 'Tier Set',
        'dungeon' => 'Dungeon',
        'delve' => 'Delves',
        'world' => 'World',
        'crafted' => 'Crafted',
    ];

    /** Picked when the request names no sources. */
    public const array DEFAULT_SOURCE_TYPES = ['raid', 'tier', 'dungeon'];

    /** Secondaries at least this share of the next higher one read as roughly equal. */
    private const float NEAR_EQUAL_RATIO = 0.9;

    private const int ITEM_CLASS_WEAPON = 2;

    private const int ARMOR_SUBCLASS_SHIELD = 6;

    private const int INVENTORY_TYPE_HELD_IN_OFF_HAND = 23;

    private const array WEAPON_TYPES = [
        0 => 'Axe', 1 => 'Axe', 2 => 'Bow', 3 => 'Gun', 4 => 'Mace', 5 => 'Mace', 6 => 'Polearm', 7 => 'Sword',
        8 => 'Sword', 9 => 'Warglaive', 10 => 'Staff', 13 => 'Fist Weapon', 15 => 'Dagger', 16 => 'Thrown', 18 => 'Crossbow', 19 => 'Wand',
        20 => 'Fishing Pole',
    ];

    private const array ARMOR_TYPES = [1 => 'Cloth', 2 => 'Leather', 3 => 'Mail', 4 => 'Plate', 6 => 'Shield'];

    /** @var array{build: string, season: array{slug: string, name: string}, instances: array<string, array{name: string, achievementId: ?int, icon: ?string}>, tracks: array<string, string>, items: list<array<string, mixed>>, sources: list<array<string, mixed>>}|null */
    private ?array $dataset = null;

    public function __construct(private readonly string $path) {}

    /**
     * @return array{build: string, season: array{slug: string, name: string}}
     */
    public function meta(): array
    {
        $dataset = $this->dataset();

        return ['build' => $dataset['build'], 'season' => $dataset['season']];
    }

    /**
     * @return array<string, array{name: string, achievementId: ?int, icon: ?string}> by journal instance ID
     */
    public function instances(): array
    {
        return $this->dataset()['instances'];
    }

    /**
     * @return array<string, string> upgrade track key => name, lowest first
     */
    public function tracks(): array
    {
        return $this->dataset()['tracks'];
    }

    /**
     * @return list<array<string, mixed>> items with their `sources` and the `rank` to link, highest
     *                                    potential item level first
     */
    public function search(GearFilter $filter): array
    {
        $results = [];

        foreach ($this->fromSources($filter->sources) as $item) {
            if ($this->matches($item, $filter)) {
                $results[] = [...$item, 'rank' => $this->rankFor($item, $filter->track)];
            }
        }

        usort($results, fn (array $a, array $b): int => [$b['itemLevel'] ?? 0, $this->statWeight($b, $filter), $a['name']]
            <=> [$a['itemLevel'] ?? 0, $this->statWeight($a, $filter), $b['name']]);

        return $results;
    }

    /**
     * Unsorted and unfiltered apart from the sources, for callers that aggregate rather than list.
     *
     * @param  list<string>  $sources  source types to include; empty means all
     * @return list<array<string, mixed>> items with their `sources` of those types
     */
    public function fromSources(array $sources): array
    {
        $dataset = $this->dataset();
        $sourcesByItem = [];

        foreach ($dataset['sources'] as $source) {
            if (! isset(self::SOURCE_TYPES[$source['type']])) {
                continue;
            }

            if ($sources === [] || in_array($source['type'], $sources, true)) {
                $sourcesByItem[$source['itemId']][] = $source;
            }
        }

        $results = [];

        foreach ($dataset['items'] as $item) {
            if (isset($sourcesByItem[$item['id']])) {
                $results[] = [...$item, 'sources' => $sourcesByItem[$item['id']]];
            }
        }

        return $results;
    }

    /**
     * Result count per filter option, each counted against the other groups' active filters so it
     * previews what picking that option yields. Stats are AND-ed, so a stat counts items that also
     * have every already picked stat.
     *
     * @return array{slots: array<string, int>, stats: array<string, int>, sources: array<string, int>, anySlot: int, anyStat: int, anySource: int}
     */
    public function counts(GearFilter $filter): array
    {
        $dataset = $this->dataset();
        $sourceTypesByItem = [];

        foreach ($dataset['sources'] as $source) {
            if (! isset(self::SOURCE_TYPES[$source['type']])) {
                continue;
            }

            $sourceTypesByItem[$source['itemId']][$source['type']] = true;
        }

        $counts = [
            'slots' => array_fill_keys(array_column(Slot::cases(), 'value'), 0),
            'stats' => array_fill_keys(Stat::SECONDARY_KEYS, 0),
            'sources' => array_fill_keys(array_keys(self::SOURCE_TYPES), 0),
            'anySlot' => 0,
            'anyStat' => 0,
            'anySource' => 0,
        ];

        foreach ($dataset['items'] as $item) {
            $sourceTypes = $sourceTypesByItem[$item['id']] ?? [];

            if ($sourceTypes === [] || ($filter->class !== null && ! $this->isUsableBy($item, $filter->class, $filter->specs))) {
                continue;
            }

            $matchesSlot = $filter->slot === null || $item['slot'] === $filter->slot->value;
            $matchesStats = $this->hasStats($item, $filter->stats);
            $matchesSource = $filter->sources === [] || array_intersect_key($sourceTypes, array_flip($filter->sources)) !== [];

            if ($matchesStats && $matchesSource) {
                $counts['anySlot']++;
                $counts['slots'][$item['slot']]++;
            }

            if ($matchesSlot && $matchesSource) {
                $counts['anyStat']++;

                foreach (Stat::SECONDARY_KEYS as $stat) {
                    if ($this->hasStats($item, [...$filter->stats, $stat])) {
                        $counts['stats'][$stat]++;
                    }
                }
            }

            if ($matchesSlot && $matchesStats) {
                $counts['anySource']++;

                foreach (array_keys($sourceTypes) as $type) {
                    $counts['sources'][$type]++;
                }
            }
        }

        return $counts;
    }

    /**
     * Secondary stats from highest to lowest, each with how it compares to the one before it.
     *
     * @param  array<string, int>  $secondaries
     * @return list<array{stat: string, relation: '='|'≈'|'>'|null}>
     */
    public static function rankSecondaries(array $secondaries): array
    {
        arsort($secondaries);

        $ranked = [];
        $previous = null;

        foreach ($secondaries as $stat => $value) {
            $ranked[] = ['stat' => $stat, 'relation' => match (true) {
                $previous === null => null,
                $value === $previous => '=',
                $value >= $previous * self::NEAR_EQUAL_RATIO => '≈',
                default => '>',
            }];
            $previous = $value;
        }

        return $ranked;
    }

    /**
     * Wowhead tooltip query for a search result: its default bonus lists plus the linked rank.
     *
     * @param  array<string, mixed>  $item
     */
    public static function wowheadParams(array $item): ?string
    {
        $rank = $item['rank'];
        $bonusLists = $rank === null ? $item['defaultBonusLists'] : [...$item['defaultBonusLists'], $rank['bonusList']];

        if ($bonusLists === []) {
            return null;
        }

        $params = 'bonus='.implode(':', $bonusLists);

        return $rank === null ? $params : "{$params}&ilvl={$rank['itemLevel']}";
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public static function typeLabel(array $item): string
    {
        if ($item['classId'] === self::ITEM_CLASS_WEAPON) {
            return self::WEAPON_TYPES[$item['subclassId']] ?? 'Weapon';
        }

        // Cloaks are cloth for every class, so the armor type would only suggest otherwise.
        if ($item['slot'] === Slot::Back->value) {
            return '';
        }

        if ($item['inventoryType'] === self::INVENTORY_TYPE_HELD_IN_OFF_HAND) {
            return 'Off-Hand';
        }

        return self::ARMOR_TYPES[$item['subclassId']] ?? '';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function matches(array $item, GearFilter $filter): bool
    {
        if ($filter->slot !== null && $item['slot'] !== $filter->slot->value) {
            return false;
        }

        if (! $this->hasStats($item, $filter->stats)) {
            return false;
        }

        return $filter->class === null || $this->isUsableBy($item, $filter->class, $filter->specs);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<string>  $stats
     */
    private function hasStats(array $item, array $stats): bool
    {
        // Crafted items take whichever secondaries the crafter picks.
        return $item['choiceStats'] || array_diff($stats, array_keys($item['secondaries'])) === [];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array{id: int, armor: int, specs: array<string, array{id: int, primary: string, weapons: list<int>, shield?: bool, off_hand?: bool}>}  $class
     * @param  list<string>  $specKeys
     */
    public function isUsableBy(array $item, array $class, array $specKeys): bool
    {
        $allowableClass = $item['allowableClass'];

        if ($allowableClass > 0 && ($allowableClass & (1 << ($class['id'] - 1))) === 0) {
            return false;
        }

        if (Slot::from($item['slot'])->isArmor() && $item['subclassId'] !== $class['armor']) {
            return false;
        }

        $specs = $specKeys === [] ? $class['specs'] : array_intersect_key($class['specs'], array_flip($specKeys));

        foreach ($specs as $spec) {
            if ($this->isUsableBySpec($item, $spec)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array{id: int, primary: string, weapons: list<int>, shield?: bool, off_hand?: bool}  $spec
     */
    private function isUsableBySpec(array $item, array $spec): bool
    {
        // The override is the loot spec list; it's how primary-less trinkets are limited to Str/Agi specs.
        if ($item['specs'] !== [] && ! in_array($spec['id'], $item['specs'], true)) {
            return false;
        }

        if ($item['primaries'] !== [] && ! in_array($spec['primary'], $item['primaries'], true)) {
            return false;
        }

        return match (true) {
            $item['classId'] === self::ITEM_CLASS_WEAPON => in_array($item['subclassId'], $spec['weapons'], true),
            $item['subclassId'] === self::ARMOR_SUBCLASS_SHIELD => $spec['shield'] ?? false,
            $item['inventoryType'] === self::INVENTORY_TYPE_HELD_IN_OFF_HAND => $spec['off_hand'] ?? false,
            default => true,
        };
    }

    /**
     * The item's top rank on `$track`, or on its reachable track nearest to it; without a track, the
     * item's best rank.
     *
     * @param  array<string, mixed>  $item
     * @return array{bonusList: int, itemLevel: int}|null
     */
    private function rankFor(array $item, ?string $track): ?array
    {
        $ranks = $item['tracks'];

        if ($ranks === []) {
            return null;
        }

        if ($track === null) {
            return collect($ranks)->sortByDesc('itemLevel')->first();
        }

        $order = array_flip(array_keys($this->tracks()));
        $nearest = collect(array_keys($ranks))->sortBy(fn (string $key): int => abs($order[$key] - $order[$track]))->first();

        return $ranks[$nearest];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function statWeight(array $item, GearFilter $filter): int
    {
        return array_sum(array_intersect_key($item['secondaries'], array_flip($filter->stats)));
    }

    /**
     * @return array{build: string, season: array{slug: string, name: string}, items: list<array<string, mixed>>, sources: list<array<string, mixed>>}
     */
    private function dataset(): array
    {
        if ($this->dataset !== null) {
            return $this->dataset;
        }

        if (! is_file($this->path)) {
            throw new RuntimeException("Gear dataset missing at {$this->path}; run `php artisan gear:import`.");
        }

        return $this->dataset = json_decode((string) file_get_contents($this->path), true, flags: JSON_THROW_ON_ERROR);
    }
}
