<?php

namespace App\Gear;

/**
 * Aggregates season content (raid, tier set, dungeon) for the statistics pages: how many items each
 * spec can use, which secondary stats they carry, and where a spec or instance comes up short.
 *
 * @phpstan-type Profile array{items: int, rated: int, presence: array<string, int>, pairs: array<string, int>}
 */
class GearStatistics
{
    /** Other sources' gear either can't reach the top upgrade track or picks its own stats, so it would blur the picture. */
    public const array SOURCES = ['raid', 'tier', 'dungeon'];

    public const array STAT_NAMES = ['crit' => 'Critical Strike', 'haste' => 'Haste', 'mastery' => 'Mastery', 'vers' => 'Versatility'];

    /** Below this many rated items, a share swings too much on a single item to call it a lean. */
    private const int MIN_RATED_FOR_SKEW = 15;

    /** @var array<string, list<array<string, mixed>>> every item under '', then by class and by class/spec */
    private array $usableItems = [];

    public function __construct(private readonly GearCatalog $catalog) {}

    /**
     * Secondary pairs in a fixed order, e.g. `crit-haste`.
     *
     * @return list<string>
     */
    public static function pairKeys(): array
    {
        $keys = [];

        foreach (Stat::SECONDARY_KEYS as $index => $first) {
            foreach (array_slice(Stat::SECONDARY_KEYS, $index + 1) as $second) {
                $keys[] = "{$first}-{$second}";
            }
        }

        return $keys;
    }

    /**
     * How often each secondary and each pair of secondaries appears. Items without fixed secondaries
     * (most trinkets) count toward `items` but not `rated`.
     *
     * @param  list<array<string, mixed>>  $items
     * @return Profile
     */
    public static function profile(array $items): array
    {
        $presence = array_fill_keys(Stat::SECONDARY_KEYS, 0);
        $pairs = array_fill_keys(self::pairKeys(), 0);
        $rated = 0;

        foreach ($items as $item) {
            if ($item['choiceStats'] || $item['secondaries'] === []) {
                continue;
            }

            $rated++;
            $stats = array_values(array_intersect(Stat::SECONDARY_KEYS, array_keys($item['secondaries'])));

            foreach ($stats as $stat) {
                $presence[$stat]++;
            }

            if (count($stats) === 2) {
                $pairs[implode('-', $stats)]++;
            }
        }

        return ['items' => count($items), 'rated' => $rated, 'presence' => $presence, 'pairs' => $pairs];
    }

    /**
     * Share of rated items that carry `$stat` (a secondary, or a pair key).
     *
     * @param  Profile  $profile
     */
    public static function share(array $profile, string $stat): float
    {
        $count = $profile['presence'][$stat] ?? $profile['pairs'][$stat];

        return $profile['rated'] === 0 ? 0.0 : $count / $profile['rated'];
    }

    /**
     * Takeaways for the summary page, as data so the page can show spec and instance icons and stat colors.
     *
     * @param  array{profile: Profile, bySource: array<string, int>}  $overall
     * @param  list<array{profile: Profile, coverage: array<string, int|null>}>  $specs
     * @param  list<array{profile: Profile}>  $instances
     * @return array{
     *     items?: array{fewest: array<string, mixed>, most: array<string, mixed>},
     *     stats?: array{rarest: string, rarestShare: float, commonest: string, commonestShare: float},
     *     gaps?: array{specs: int, of: int, slot: string, slotSpecs: int},
     *     lean?: array{instance: array<string, mixed>, stat: string, share: float, overall: float},
     * }
     */
    public static function highlights(array $overall, array $specs, array $instances): array
    {
        $highlights = [];

        if ($specs !== []) {
            $byItems = collect($specs)->sortBy(fn (array $spec): int => $spec['profile']['items']);
            $highlights['items'] = ['fewest' => $byItems->first(), 'most' => $byItems->last()];
        }

        if ($overall['profile']['rated'] > 0) {
            $shares = collect(Stat::SECONDARY_KEYS)->mapWithKeys(fn (string $stat): array => [$stat => self::share($overall['profile'], $stat)])->sort();
            $highlights['stats'] = [
                'rarest' => $shares->keys()->first(),
                'rarestShare' => $shares->first(),
                'commonest' => $shares->keys()->last(),
                'commonestShare' => $shares->last(),
            ];
        }

        $gapSlots = [];
        $specsWithGaps = 0;

        foreach ($specs as $spec) {
            $gaps = array_keys(array_filter($spec['coverage'], fn (?int $count): bool => $count === 0));
            $specsWithGaps += $gaps === [] ? 0 : 1;

            foreach ($gaps as $slot) {
                $gapSlots[$slot] = ($gapSlots[$slot] ?? 0) + 1;
            }
        }

        if ($gapSlots !== []) {
            arsort($gapSlots);
            $highlights['gaps'] = ['specs' => $specsWithGaps, 'of' => count($specs), 'slot' => array_key_first($gapSlots), 'slotSpecs' => reset($gapSlots)];
        }

        $leans = [];

        foreach ($instances as $instance) {
            if ($instance['profile']['rated'] < self::MIN_RATED_FOR_SKEW) {
                continue;
            }

            foreach (Stat::SECONDARY_KEYS as $stat) {
                $leans[] = ['instance' => $instance, 'stat' => $stat, 'share' => self::share($instance['profile'], $stat), 'overall' => self::share($overall['profile'], $stat)];
            }
        }

        if ($leans !== []) {
            $highlights['lean'] = collect($leans)->sortByDesc(fn (array $lean): float => abs($lean['share'] - $lean['overall']))->first();
        }

        return $highlights;
    }

    /**
     * @return array{profile: Profile, bySource: array<string, int>}
     */
    public function overall(): array
    {
        $items = $this->catalog->fromSources(self::SOURCES);

        return ['profile' => self::profile($items), 'bySource' => $this->countBySource($items)];
    }

    /**
     * One row per spec, classes alphabetically.
     *
     * @return list<array{classKey: string, specKey: string, className: string, specName: string, color: string, icon: string, bySource: array<string, int>, profile: Profile, coverage: array<string, int|null>}>
     */
    public function specs(): array
    {
        $rows = [];

        foreach (collect(config('classes'))->sortBy('name') as $classKey => $class) {
            foreach ($class['specs'] as $specKey => $spec) {
                $usable = $this->usableItems($classKey, $specKey);
                $seasonal = $this->seasonal($usable);

                $rows[] = [
                    'classKey' => $classKey,
                    'specKey' => $specKey,
                    'className' => $class['name'],
                    'specName' => $spec['name'],
                    'color' => $class['color'],
                    'icon' => $spec['icon'],
                    'bySource' => $this->countBySource($seasonal),
                    'profile' => self::profile($seasonal),
                    'coverage' => $this->coverage($usable, $seasonal),
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array{bySource: array<string, int>, profile: Profile, slots: array<string, array{items: int, bySource: array<string, int>, pairs: array<string, int>, other: int}>}
     */
    public function spec(string $classKey, string $specKey): array
    {
        $usable = $this->usableItems($classKey, $specKey);
        $seasonal = $this->seasonal($usable);
        $slots = [];

        foreach ($this->coverage($usable, $seasonal) as $slot => $count) {
            if ($count === null) {
                continue;
            }

            $inSlot = array_values(array_filter($seasonal, fn (array $item): bool => $item['slot'] === $slot));
            $profile = self::profile($inSlot);

            $slots[$slot] = [
                'items' => $count,
                'bySource' => $this->countBySource($inSlot),
                'pairs' => $profile['pairs'],
                // single-secondary and stat-less items (most trinkets) have no pair column
                'other' => $profile['items'] - array_sum($profile['pairs']),
            ];
        }

        return ['bySource' => $this->countBySource($seasonal), 'profile' => self::profile($seasonal), 'slots' => $slots];
    }

    /**
     * Raids first, then dungeons. Tier pieces count toward the raid whose tokens they come from.
     *
     * @return list<array{id: int, name: string, type: string, icon: ?string, profile: Profile}>
     */
    public function instances(): array
    {
        $groups = [];

        foreach ($this->catalog->fromSources(self::SOURCES) as $item) {
            foreach ($item['sources'] as $source) {
                if ($source['instanceId'] === null) {
                    continue;
                }

                $groups[$source['instanceId']]['name'] = $source['instance'];
                $groups[$source['instanceId']]['type'] = $source['type'] === 'dungeon' ? 'dungeon' : 'raid';
                $groups[$source['instanceId']]['items'][$item['id']] = $item;
            }
        }

        $rows = [];
        $icons = $this->catalog->instances();

        foreach ($groups as $id => $group) {
            $rows[] = [
                'id' => $id,
                'name' => $group['name'],
                'type' => $group['type'],
                'icon' => $icons[$id]['icon'] ?? null,
                'profile' => self::profile(array_values($group['items'])),
            ];
        }

        usort($rows, fn (array $a, array $b): int => [$a['type'] === 'dungeon', $a['name']] <=> [$b['type'] === 'dungeon', $b['name']]);

        return $rows;
    }

    /**
     * @return array{name: string, type: string, icon: ?string, profile: Profile, encounters: list<array{name: string, profile: Profile}>, armor: array<string, array<string, int>>}|null
     */
    public function instance(int $instanceId): ?array
    {
        $items = [];
        $encounters = [];
        $name = null;
        $type = 'raid';

        foreach ($this->catalog->fromSources(self::SOURCES) as $item) {
            foreach ($item['sources'] as $source) {
                if ($source['instanceId'] !== $instanceId) {
                    continue;
                }

                $name = $source['instance'];
                $type = $source['type'] === 'dungeon' ? 'dungeon' : $type;
                $items[$item['id']] = $item;
                $encounters[$source['encounter'] ?? GearCatalog::SOURCE_TYPES[$source['type']]][$item['id']] = $item;
            }
        }

        if ($name === null) {
            return null;
        }

        $armor = [];

        foreach ($items as $item) {
            if (Slot::from($item['slot'])->isArmor()) {
                $armorType = GearCatalog::typeLabel($item);
                $armor[$item['slot']][$armorType] = ($armor[$item['slot']][$armorType] ?? 0) + 1;
            }
        }

        return [
            'name' => $name,
            'type' => $type,
            'icon' => $this->catalog->instances()[$instanceId]['icon'] ?? null,
            'profile' => self::profile(array_values($items)),
            'encounters' => collect($encounters)
                ->map(fn (array $items, string $encounter): array => ['name' => $encounter, 'profile' => self::profile(array_values($items))])
                ->values()
                ->all(),
            'armor' => $armor,
        ];
    }

    /**
     * Items the spec can use from any source, so a slot season content leaves empty can be told apart
     * from a slot the spec never fills.
     *
     * @return list<array<string, mixed>>
     */
    private function usableItems(string $classKey, string $specKey): array
    {
        $class = config("classes.{$classKey}");

        // narrowing by class first spares most per-spec checks
        $this->usableItems[$classKey] ??= array_values(array_filter(
            $this->usableItems[''] ??= $this->catalog->fromSources([]),
            fn (array $item): bool => $this->catalog->isUsableBy($item, $class, []),
        ));

        return $this->usableItems["{$classKey}/{$specKey}"] ??= array_values(array_filter(
            $this->usableItems[$classKey],
            fn (array $item): bool => $this->catalog->isUsableBy($item, $class, [$specKey]),
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function seasonal(array $items): array
    {
        return array_values(array_filter($items, fn (array $item): bool => array_intersect(array_column($item['sources'], 'type'), self::SOURCES) !== []));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, int> items per source type; an item from several types counts for each
     */
    private function countBySource(array $items): array
    {
        $counts = array_fill_keys(self::SOURCES, 0);

        foreach ($items as $item) {
            foreach (array_unique(array_column($item['sources'], 'type')) as $type) {
                if (isset($counts[$type])) {
                    $counts[$type]++;
                }
            }
        }

        return $counts;
    }

    /**
     * @param  list<array<string, mixed>>  $usable
     * @param  list<array<string, mixed>>  $seasonal
     * @return array<string, int|null> season items per slot; null when no source at all has one for the spec
     */
    private function coverage(array $usable, array $seasonal): array
    {
        $usableSlots = array_count_values(array_column($usable, 'slot'));
        $seasonalSlots = array_count_values(array_column($seasonal, 'slot'));
        $coverage = [];

        foreach (Slot::cases() as $slot) {
            $coverage[$slot->value] = isset($usableSlots[$slot->value]) ? $seasonalSlots[$slot->value] ?? 0 : null;
        }

        return $coverage;
    }
}
