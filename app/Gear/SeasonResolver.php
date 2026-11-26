<?php

namespace App\Gear;

use Closure;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resolves every item obtainable in the configured season from DB2 exports.
 * The chain is documented in docs/dbc-current-season.md.
 *
 * @phpstan-type Source array{itemId: int, type: string, instanceId: ?int, instance: ?string, encounterId: ?int, encounter: ?string, itemLevel: ?int}
 * @phpstan-type Rank array{bonusList: int, itemLevel: int}
 */
class SeasonResolver
{
    private const string CURRENT_SEASON_TIER = '9000';

    private const int MODIFIER_TIME_EVENT_PASSED = 289;

    private const array RAID_DIFFICULTIES = ['14', '15', '16', '17'];

    private const string DUNGEON_MYTHIC_DIFFICULTY = '23';

    private const int ITEM_CLASS_WEAPON = 2;

    private const int ITEM_CLASS_ARMOR = 4;

    private const int ITEM_CLASS_MISC = 15;

    private const int ARMOR_SUBCLASS_COSMETIC = 5;

    private const int INVENTORY_TYPE_NECK = 2;

    private const int INVENTORY_TYPE_TRINKET = 12;

    private const int BONUS_SOCKET = 6;

    // Tertiary stats and chance sockets carry this type; it marks a random roll, not a guaranteed bonus.
    private const int BONUS_RANDOM_ROLL_TOAST = 9;

    private const int BONUS_ITEM_EFFECT = 23;

    private const int BONUS_UPGRADE_TRACK = 34;

    private const int BONUS_LIMIT_CATEGORY = 35;

    private const int BONUS_ITEM_LEVEL = 49;

    private const int ENTRY_FLAG_ABOVE_CAP = 0x1;

    private const int ACHIEVEMENT_FLAG_STATISTIC = 0x1;

    /** @var array<string, list<array<string, string>>> */
    private array $treeNodes = [];

    /** @var array<string, list<array<string, string>>> */
    private array $challengeModeOverrides = [];

    /** @var array<int, array{key: string, name: string, top: Rank, aboveCap: Rank, ascendant?: Rank}> season group ID => track, lowest first */
    private array $tracks = [];

    /** @var array<string, array<int, bool>> */
    private array $reachMemo = [];

    private int $endTimeEvent = PHP_INT_MAX;

    /**
     * @param  array{slug: string, name: string, expansion_id: int, start_time_event: int, ilvl_scaling_id: int, top_rank_points_mod_sets: list<int>, ascendant_bonus_lists: list<int>, min_group: int, catalyst_bonus_trees: list<int>, crafted: array{quality_bonus_list: int, reagent_bonus_list: int, item_levels: array<string, int>}}  $season
     * @param  array<string, array{bonus_trees: list<int>, max_group?: int}>  $expansion
     */
    public function __construct(
        private readonly Db2Directory $db2,
        private readonly array $season,
        private readonly array $expansion,
    ) {}

    /**
     * @return array{build: string, hotfixes: array<string, string>, season: array{slug: string, name: string}, instances: array<string, array{name: string, achievementId: ?int}>, tracks: array<string, string>, items: list<array<string, mixed>>, sources: list<Source>}
     */
    public function resolve(string $build): array
    {
        $instances = $this->seasonInstances();
        $journal = $this->journalSources($instances);
        $treeItems = $this->bonusTreeItems();
        $crafted = $this->craftedItems();

        $candidateIds = array_fill_keys(array_merge(
            array_column($journal, 'itemId'),
            array_keys($treeItems),
            $crafted,
        ), true);

        $items = $this->db2->keyed('Item', filter: fn (array $row): bool => isset($candidateIds[$row['ID']]));
        $sparse = $this->db2->keyed('ItemSparse', filter: fn (array $row): bool => isset($candidateIds[$row['ID']]));

        $this->loadBonusTrees();
        $itemTrees = $this->db2->grouped('ItemXBonusTree', 'ItemID', fn (array $row): bool => isset($candidateIds[$row['ItemID']]));

        $sources = [];
        $tierInstance = null;

        foreach ($journal as $source) {
            $item = $items[$source['itemId']] ?? null;

            if ($item !== null && (int) $item['ClassID'] === self::ITEM_CLASS_MISC && (int) $item['SubclassID'] === 0) {
                // Tier tokens: the set pieces are listed directly, attributed to the raid that drops tokens.
                $tierInstance ??= ['id' => $source['instanceId'], 'name' => $source['instance']];

                continue;
            }

            $sources[] = $source;
        }

        foreach ($treeItems as $itemId => $types) {
            $isExpansionItem = (int) ($sparse[$itemId]['ExpansionID'] ?? -1) === $this->season['expansion_id'];

            if (! $isExpansionItem) {
                continue;
            }

            foreach ($types as $type) {
                if ($type === 'catalyst' && (int) $sparse[$itemId]['ItemSet'] !== 0) {
                    $type = 'tier';
                }

                $sources[] = [
                    'itemId' => (int) $itemId,
                    'type' => $type,
                    'instanceId' => $type === 'tier' ? $tierInstance['id'] ?? null : null,
                    'instance' => $type === 'tier' ? $tierInstance['name'] ?? null : null,
                    'encounterId' => null,
                    'encounter' => null,
                ];
            }
        }

        foreach ($crafted as $itemId) {
            if ((int) ($sparse[$itemId]['ExpansionID'] ?? -1) === $this->season['expansion_id']) {
                $sources[] = ['itemId' => (int) $itemId, 'type' => 'crafted', 'instanceId' => null, 'instance' => null, 'encounterId' => null, 'encounter' => null];
            }
        }

        $sources = array_values(array_filter($sources, fn (array $source): bool => $this->isGear($items[$source['itemId']] ?? null, $sparse[$source['itemId']] ?? null)));
        $isAscendable = fn (int $itemId): bool => (int) $items[$itemId]['ClassID'] === self::ITEM_CLASS_WEAPON
            || in_array((int) $sparse[$itemId]['InventoryType'], [self::INVENTORY_TYPE_NECK, self::INVENTORY_TYPE_TRINKET], true);
        $sources = $this->withMinimumTrack($this->withItemLevels($sources, $itemTrees, $isAscendable));

        return [
            'build' => $build,
            'hotfixes' => $this->db2->hotfixes(),
            'season' => ['slug' => $this->season['slug'], 'name' => $this->season['name']],
            'instances' => $this->instanceAchievements($instances),
            'tracks' => array_column($this->tracks, 'name', 'key'),
            'items' => $this->describeItems($sources, $items, $sparse, $itemTrees),
            'sources' => $this->sortSources(array_map(fn (array $source): array => array_diff_key($source, ['tracks' => true]), $sources)),
        ];
    }

    /**
     * @return array<string, array{name: string, type: string, mapId: string}> JournalInstance ID => details
     */
    private function seasonInstances(): array
    {
        $tier = null;

        foreach ($this->db2->rows('JournalTier') as $row) {
            if ($row['Expansion'] === self::CURRENT_SEASON_TIER) {
                $tier = $row['ID'];
            }
        }

        if ($tier === null) {
            throw new RuntimeException('No "Current Season" journal tier found.');
        }

        $tierInstances = $this->db2->keyed('JournalTierXInstance', filter: fn (array $row): bool => $row['JournalTierID'] === $tier);
        $conditionIds = array_fill_keys(array_column($tierInstances, 'AvailabilityCondition'), true);
        $conditions = $this->db2->keyed('PlayerCondition', filter: fn (array $row): bool => isset($conditionIds[$row['ID']]));
        $treeIds = array_fill_keys(array_column($conditions, 'ModifierTreeID'), true);
        $modifiers = $this->db2->grouped('ModifierTree', 'Parent', fn (array $row): bool => isset($treeIds[$row['Parent']])
            && (int) $row['Type'] === self::MODIFIER_TIME_EVENT_PASSED);

        $instanceIds = [];

        foreach ($tierInstances as $row) {
            $children = $modifiers[$conditions[$row['AvailabilityCondition']]['ModifierTreeID'] ?? ''] ?? [];
            $start = collect($children)->firstWhere('Operator', '2');
            $end = collect($children)->firstWhere('Operator', '3');

            if ($start !== null && (int) $start['Asset'] === $this->season['start_time_event']) {
                $instanceIds[$row['JournalInstanceID']] = true;
                $this->endTimeEvent = $end !== null ? (int) $end['Asset'] : PHP_INT_MAX;
            }
        }

        if ($instanceIds === []) {
            throw new RuntimeException("No journal instances start at TimeEvent {$this->season['start_time_event']}.");
        }

        $journalInstances = $this->db2->keyed('JournalInstance', filter: fn (array $row): bool => isset($instanceIds[$row['ID']]));
        $mapIds = array_fill_keys(array_column($journalInstances, 'MapID'), true);
        $maps = $this->db2->keyed('Map', filter: fn (array $row): bool => isset($mapIds[$row['ID']]));
        $result = [];

        foreach ($journalInstances as $id => $instance) {
            $type = ($maps[$instance['MapID']]['InstanceType'] ?? null) === '2' ? 'raid' : 'dungeon';
            $result[$id] = ['name' => $instance['Name_lang'], 'type' => $type, 'mapId' => $instance['MapID']];
        }

        return $result;
    }

    /**
     * The achievement whose icon stands for each instance: the one named after the instance, else one
     * with the icon most of its achievements share. Statistics are skipped since they all use one
     * generic icon. `gear:import` resolves the icon itself through Wowhead.
     *
     * @param  array<string, array{name: string, type: string, mapId: string}>  $instances
     * @return array<string, array{name: string, achievementId: ?int}>
     */
    private function instanceAchievements(array $instances): array
    {
        $mapIds = array_fill_keys(array_column($instances, 'mapId'), true);
        $achievements = $this->db2->grouped('Achievement', 'Instance_ID', fn (array $row): bool => isset($mapIds[$row['Instance_ID']])
            && $row['IconFileID'] !== '0'
            && ((int) $row['Flags'] & self::ACHIEVEMENT_FLAG_STATISTIC) === 0);
        $normalize = fn (string $name): string => strtolower((string) preg_replace('/^The /', '', $name));
        $result = [];

        foreach ($instances as $id => $instance) {
            $candidates = $achievements[$instance['mapId']] ?? [];
            $named = collect($candidates)->first(fn (array $row): bool => $normalize($row['Title_lang']) === $normalize($instance['name']));
            $counts = array_count_values(array_column($candidates, 'IconFileID'));
            arsort($counts);
            $achievement = $named ?? collect($candidates)->firstWhere('IconFileID', (string) array_key_first($counts));

            $result[$id] = [
                'name' => $instance['name'],
                'achievementId' => $achievement === null ? null : (int) $achievement['ID'],
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, array{name: string, type: string, mapId: string}>  $instances
     * @return list<array{itemId: int, type: string, instanceId: int, instance: string, encounterId: int, encounter: string}>
     */
    private function journalSources(array $instances): array
    {
        $encounters = $this->db2->keyed('JournalEncounter', filter: fn (array $row): bool => isset($instances[$row['JournalInstanceID']]));
        $entries = $this->db2->keyed('JournalEncounterItem', filter: fn (array $row): bool => isset($encounters[$row['JournalEncounterID']]));
        $expressionIds = array_fill_keys(array_column($entries, 'WorldStateExpressionID'), true);
        $expressions = $this->db2->keyed('WorldStateExpression', filter: fn (array $row): bool => isset($expressionIds[$row['ID']]));
        $difficulties = $this->db2->grouped('JournalItemXDifficulty', 'JournalEncounterItemID', fn (array $row): bool => isset($entries[$row['JournalEncounterItemID']]));
        $timeEventPassed = fn (int $timeEvent): bool => $timeEvent < $this->endTimeEvent;

        $result = [];

        foreach ($entries as $entry) {
            $expression = $expressions[$entry['WorldStateExpressionID']] ?? null;

            if ($expression !== null && ! WorldStateExpression::evaluate($expression['Expression'], $timeEventPassed)) {
                continue;
            }

            $encounter = $encounters[$entry['JournalEncounterID']];
            $instance = $instances[$encounter['JournalInstanceID']];
            $allowed = $instance['type'] === 'dungeon' ? [self::DUNGEON_MYTHIC_DIFFICULTY] : self::RAID_DIFFICULTIES;
            $entryDifficulties = array_column($difficulties[$entry['ID']] ?? [], 'DifficultyID');

            if ($entryDifficulties !== [] && array_intersect($entryDifficulties, $allowed) === []) {
                continue;
            }

            $result[] = [
                'itemId' => (int) $entry['ItemID'],
                'type' => $instance['type'],
                'instanceId' => (int) $encounter['JournalInstanceID'],
                'instance' => $instance['name'],
                'encounterId' => (int) $encounter['ID'],
                'encounter' => $encounter['Name_lang'],
            ];
        }

        return $result;
    }

    /**
     * @return array<string, list<string>> item ID => source types
     */
    private function bonusTreeItems(): array
    {
        $treeTypes = [];

        foreach ($this->season['catalyst_bonus_trees'] as $tree) {
            $treeTypes[$tree][] = 'catalyst';
        }

        foreach (['delve', 'world'] as $type) {
            foreach ($this->expansion[$type]['bonus_trees'] as $tree) {
                $treeTypes[$tree][] = $type;
            }
        }

        $result = [];

        foreach ($this->db2->rows('ItemXBonusTree') as $row) {
            foreach ($treeTypes[(int) $row['ItemBonusTreeID']] ?? [] as $type) {
                $result[$row['ItemID']][$type] = $type;
            }
        }

        return array_map(array_values(...), $result);
    }

    /**
     * @return list<string>
     */
    private function craftedItems(): array
    {
        $trees = array_fill_keys($this->expansion['crafted']['bonus_trees'], true);
        $result = [];

        foreach ($this->db2->rows('CraftingData') as $row) {
            if ($row['CraftedItemID'] !== '0' && isset($trees[(int) $row['ItemBonusTreeID']])) {
                $result[$row['CraftedItemID']] = $row['CraftedItemID'];
            }
        }

        return array_values($result);
    }

    /**
     * @param  array<string, string>|null  $item
     * @param  array<string, string>|null  $sparse
     */
    private function isGear(?array $item, ?array $sparse): bool
    {
        if ($item === null || $sparse === null || Slot::fromInventoryType((int) $sparse['InventoryType']) === null) {
            return false;
        }

        $class = (int) $item['ClassID'];
        $isEquipment = $class === self::ITEM_CLASS_WEAPON
            || ($class === self::ITEM_CLASS_ARMOR && (int) $item['SubclassID'] !== self::ARMOR_SUBCLASS_COSMETIC);

        // Some journal weapons are transmog-only and carry no stats; trinkets can be effect-only.
        $hasStats = (int) $sparse['InventoryType'] === self::INVENTORY_TYPE_TRINKET
            || collect(range(0, 9))->contains(fn (int $index): bool => (int) $sparse["StatPercentEditor_{$index}"] !== 0);

        return $isEquipment && $hasStats;
    }

    private function loadBonusTrees(): void
    {
        $this->treeNodes = $this->db2->grouped('ItemBonusTreeNode', 'ParentItemBonusTreeID');

        $timeEventPassed = fn (int $timeEvent): bool => $timeEvent < $this->endTimeEvent;
        $this->challengeModeOverrides = $this->db2->grouped('ChallengeModeItemBonusOverride', 'SrcItemBonusTreeID', fn (array $row): bool => ((int) $row['RequiredTimeEventPassed'] === 0 || $timeEventPassed((int) $row['RequiredTimeEventPassed']))
            && ((int) $row['RequiredTimeEventNotPassed'] === 0 || ! $timeEventPassed((int) $row['RequiredTimeEventNotPassed'])));

        $groups = $this->db2->keyed('ItemBonusListGroup', filter: fn (array $row): bool => (int) $row['ItemGroupIlvlScalingID'] === $this->season['ilvl_scaling_id']);
        $topLists = [];
        $ascendantLists = array_fill_keys(array_map(strval(...), $this->season['ascendant_bonus_lists']), null);

        foreach ($this->db2->rows('ItemBonusListGroupEntry') as $entry) {
            $group = $entry['ItemBonusListGroupID'];

            if (! isset($groups[$group])) {
                continue;
            }

            if (array_key_exists($entry['ItemBonusListID'], $ascendantLists)) {
                $ascendantLists[$entry['ItemBonusListID']] = $group;
            }

            $tier = ((int) $entry['Flags'] & self::ENTRY_FLAG_ABOVE_CAP) !== 0 ? 'aboveCap' : 'top';

            if (! isset($topLists[$group][$tier]) || (int) $entry['SequenceValue'] > $topLists[$group][$tier]['rank']) {
                $topLists[$group][$tier] = ['rank' => (int) $entry['SequenceValue'], 'list' => $entry['ItemBonusListID']];
            }
        }

        $listIds = array_map(fn (): bool => true, $ascendantLists);

        foreach ($topLists as $tiers) {
            foreach ($tiers as $top) {
                $listIds[$top['list']] = true;
            }
        }

        $scalingIds = [];
        $nameIds = [];

        foreach ($this->db2->rows('ItemBonus') as $bonus) {
            if (! isset($listIds[$bonus['ParentItemBonusListID']])) {
                continue;
            }

            match ((int) $bonus['Type']) {
                self::BONUS_ITEM_LEVEL => $scalingIds[$bonus['ParentItemBonusListID']] = $bonus['Value_0'],
                self::BONUS_UPGRADE_TRACK => $nameIds[$bonus['ParentItemBonusListID']] = $bonus['Value_1'],
                default => null,
            };
        }

        $scaling = $this->db2->keyed('ItemScalingConfig', filter: fn (array $row): bool => in_array($row['ID'], $scalingIds, true));
        $names = $this->db2->keyed('SharedString', filter: fn (array $row): bool => in_array($row['ID'], $nameIds, true));
        $rank = fn (array $top): array => ['bonusList' => (int) $top['list'], 'itemLevel' => (int) $scaling[$scalingIds[$top['list']]]['ItemLevel']];

        foreach ($topLists as $group => $tiers) {
            if (! isset($tiers['top'])) {
                continue;
            }

            $name = $names[$nameIds[$tiers['top']['list']] ?? '']['String_lang'] ?? "Track {$group}";
            $top = $rank($tiers['top']);

            $this->tracks[$group] = [
                'key' => Str::slug($name),
                'name' => $name,
                'top' => $top,
                'aboveCap' => isset($tiers['aboveCap']) ? $rank($tiers['aboveCap']) : $top,
            ];
        }

        if ($this->tracks === []) {
            throw new RuntimeException("No upgrade tracks use ItemGroupIlvlScalingID {$this->season['ilvl_scaling_id']}.");
        }

        foreach ($ascendantLists as $list => $group) {
            if ($group === null || ! isset($this->tracks[$group])) {
                throw new RuntimeException("Ascendant bonus list {$list} isn't on a season upgrade track.");
            }

            $this->tracks[$group]['ascendant'] = $rank(['list' => $list]);
        }

        uasort($this->tracks, fn (array $a, array $b): int => $a['top']['itemLevel'] <=> $b['top']['itemLevel']);
    }

    /**
     * Season upgrade groups a bonus tree can award in any context, each flagged when some context
     * drops it straight at the group's highest above-cap rank.
     *
     * @return array<string, bool>
     */
    private function reachableGroups(string $tree): array
    {
        if (isset($this->reachMemo[$tree])) {
            return $this->reachMemo[$tree];
        }

        $this->reachMemo[$tree] = [];
        $groups = [];
        $merge = function (array $reached) use (&$groups): void {
            foreach ($reached as $group => $dropsAboveCap) {
                $groups[$group] = ($groups[$group] ?? false) || $dropsAboveCap;
            }
        };

        foreach ($this->treeNodes[$tree] ?? [] as $node) {
            if (isset($this->tracks[$node['ChildItemBonusListGroupID']])) {
                $merge([$node['ChildItemBonusListGroupID'] => in_array((int) $node['IblGroupPointsModSetID'], $this->season['top_rank_points_mod_sets'], true)]);
            }

            if ($node['ChildItemBonusTreeID'] !== '0') {
                $merge($this->reachableGroups($node['ChildItemBonusTreeID']));
            }
        }

        foreach ($this->challengeModeOverrides[$tree] ?? [] as $override) {
            $merge($this->reachableGroups($override['DstItemBonusTreeID']));
        }

        return $this->reachMemo[$tree] = $groups;
    }

    /**
     * @param  list<array<string, mixed>>  $sources
     * @param  array<string, list<array<string, string>>>  $itemTrees
     * @param  Closure(int): bool  $isAscendable
     * @return list<Source&array{tracks: array<string, Rank>}>
     */
    private function withItemLevels(array $sources, array $itemTrees, Closure $isAscendable): array
    {
        $caps = [];

        foreach (['delve', 'world'] as $type) {
            $caps[$type] = $this->tracks[(string) $this->expansion[$type]['max_group']]['top']['itemLevel'] ?? PHP_INT_MAX;
        }

        $craftedTracks = $this->craftedTracks();

        foreach ($sources as &$source) {
            $source['tracks'] = [];

            if ($source['type'] === 'crafted') {
                $source['tracks'] = $craftedTracks;
                $source['itemLevel'] = max(array_column($craftedTracks, 'itemLevel'));

                continue;
            }

            foreach ($itemTrees[$source['itemId']] ?? [] as $row) {
                foreach ($this->reachableGroups($row['ItemBonusTreeID']) as $group => $dropsAboveCap) {
                    $track = $this->tracks[$group];

                    if ($track['top']['itemLevel'] > ($caps[$source['type']] ?? PHP_INT_MAX)) {
                        continue;
                    }

                    $rank = $dropsAboveCap ? $track['aboveCap'] : $track['top'];

                    if (isset($track['ascendant']) && $track['ascendant']['itemLevel'] > $rank['itemLevel'] && $isAscendable($source['itemId'])) {
                        $rank = $track['ascendant'];
                    }

                    if ($rank['itemLevel'] > ($source['tracks'][$track['key']]['itemLevel'] ?? 0)) {
                        $source['tracks'][$track['key']] = $rank;
                    }
                }
            }

            $source['itemLevel'] = $source['tracks'] === [] ? null : max(array_column($source['tracks'], 'itemLevel'));
        }

        return $sources;
    }

    /**
     * @return array<string, Rank>
     */
    private function craftedTracks(): array
    {
        $config = $this->season['crafted'];
        $tracks = [];

        foreach ($config['item_levels'] as $key => $itemLevel) {
            if (! in_array($key, array_column($this->tracks, 'key'), true)) {
                throw new RuntimeException("Crafted item level track {$key} isn't a season upgrade track.");
            }

            $tracks[$key] = ['bonusList' => $config['reagent_bonus_list'], 'itemLevel' => $itemLevel];
        }

        return $tracks;
    }

    /**
     * Drops sources without an upgrade track or whose best track is below the configured minimum.
     * Crafted items are exempt since they reach every track through reagents.
     *
     * @param  list<Source&array{tracks: array<string, Rank>}>  $sources
     * @return list<Source&array{tracks: array<string, Rank>}>
     */
    private function withMinimumTrack(array $sources): array
    {
        $minGroup = $this->season['min_group'];

        if (! isset($this->tracks[$minGroup])) {
            throw new RuntimeException("Minimum upgrade group {$minGroup} isn't a season upgrade track.");
        }

        // Tracks don't overlap at their top rank, so comparing top item levels orders them.
        $minItemLevel = $this->tracks[$minGroup]['top']['itemLevel'];

        return array_values(array_filter($sources, fn (array $source): bool => $source['type'] === 'crafted'
            || ($source['itemLevel'] !== null && $source['itemLevel'] >= $minItemLevel)));
    }

    /**
     * @param  list<Source&array{tracks: array<string, Rank>}>  $sources
     * @param  array<string, array<string, string>>  $items
     * @param  array<string, array<string, string>>  $sparse
     * @param  array<string, list<array<string, string>>>  $itemTrees
     * @return list<array<string, mixed>>
     */
    private function describeItems(array $sources, array $items, array $sparse, array $itemTrees): array
    {
        $itemLevels = [];
        $itemTracks = [];

        foreach ($sources as $source) {
            $itemLevels[$source['itemId']] = max($itemLevels[$source['itemId']] ?? null, $source['itemLevel']);

            foreach ($source['tracks'] as $key => $rank) {
                if ($rank['itemLevel'] > ($itemTracks[$source['itemId']][$key]['itemLevel'] ?? 0)) {
                    $itemTracks[$source['itemId']][$key] = $rank;
                }
            }
        }

        $defaultBonusLists = array_map(fn (array $trees): array => $this->defaultBonusLists($trees), array_intersect_key($itemTrees, $itemLevels));

        foreach ($sources as $source) {
            if ($source['type'] === 'crafted') {
                $defaultBonusLists[$source['itemId']] = array_values(array_unique([...$defaultBonusLists[$source['itemId']] ?? [], $this->season['crafted']['quality_bonus_list']]));
            }
        }
        $effects = $this->effects($itemLevels, $defaultBonusLists);
        $embellishedCategories = $this->db2->keyed('ItemLimitCategory', filter: fn (array $row): bool => str_ends_with($row['Name_lang'], 'Embellished'));
        $specOverrides = $this->db2->grouped('ItemSpecOverride', 'ItemID', fn (array $row): bool => isset($itemLevels[$row['ItemID']]));
        $guaranteedBonuses = $this->guaranteedBonuses($itemLevels, $sparse, $itemTrees);
        $setIds = array_fill_keys(array_filter(array_map(fn (int $id): string => $sparse[$id]['ItemSet'], array_keys($itemLevels))), true);
        $sets = $this->db2->keyed('ItemSet', filter: fn (array $row): bool => isset($setIds[$row['ID']]));
        $calculator = StatCalculator::fromDirectory($this->db2);
        $result = [];

        ksort($itemLevels);

        foreach ($itemLevels as $id => $itemLevel) {
            $row = $sparse[$id];
            $inventoryType = (int) $row['InventoryType'];
            $allocations = [];

            for ($index = 0; $index < 10; $index++) {
                $stat = (int) $row["StatModifier_bonusStat_{$index}"];
                $allocation = (int) $row["StatPercentEditor_{$index}"];

                if ($stat !== -1 && $allocation !== 0) {
                    $allocations[$stat] = ($allocations[$stat] ?? 0) + $allocation;
                }
            }

            $secondaries = [];
            $primaries = [];
            $hasChoiceStats = false;

            foreach ($allocations as $statId => $allocation) {
                $stat = Stat::tryFrom($statId);
                $primaries = array_merge($primaries, $stat?->primaries() ?? []);
                $hasChoiceStats = $hasChoiceStats || $stat?->key() === 'choice';

                if (in_array($stat?->key(), Stat::SECONDARY_KEYS, true)) {
                    $secondaries[$stat->key()] = $allocation;
                }
            }

            arsort($secondaries);
            $nativeSockets = count(array_filter([$row['SocketType_0'], $row['SocketType_1'], $row['SocketType_2']], fn (string $type): bool => $type !== '0'));
            $setId = (int) $row['ItemSet'];

            $result[] = [
                'id' => $id,
                'name' => $row['Display_lang'],
                'slot' => Slot::fromInventoryType($inventoryType)->value,
                'inventoryType' => $inventoryType,
                'classId' => (int) $items[$id]['ClassID'],
                'subclassId' => (int) $items[$id]['SubclassID'],
                'quality' => (int) $row['OverallQualityID'],
                'allowableClass' => (int) $row['AllowableClass'],
                'specs' => array_map(intval(...), array_column($specOverrides[$id] ?? [], 'SpecID')),
                'primaries' => array_values(array_unique($primaries)),
                'secondaries' => $secondaries === [] ? (object) [] : $secondaries,
                'choiceStats' => $hasChoiceStats,
                'itemLevel' => $itemLevel,
                'tracks' => $this->orderedTracks($itemTracks[$id] ?? []),
                'defaultBonusLists' => $defaultBonusLists[$id] ?? [],
                'stats' => $itemLevel === null ? null : $calculator->compute($inventoryType, (int) $items[$id]['SubclassID'], $allocations, $itemLevel),
                'sockets' => $nativeSockets + ($guaranteedBonuses[$id]['sockets'] ?? 0),
                'onUse' => $effects[$id]['onUse'] ?? false,
                'onEquip' => $effects[$id]['onEquip'] ?? false,
                'embellished' => isset($embellishedCategories[$guaranteedBonuses[$id]['limitCategory'] ?? $row['LimitCategory']]),
                'itemSet' => $setId === 0 ? null : ['id' => $setId, 'name' => $sets[$setId]['Name_lang'] ?? null],
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, Rank>  $tracks
     * @return array<string, Rank>|object
     */
    private function orderedTracks(array $tracks): array|object
    {
        $ordered = array_intersect_key(array_flip(array_column($this->tracks, 'key')), $tracks);

        return $ordered === [] ? (object) [] : array_replace($ordered, $tracks);
    }

    /**
     * Bonus lists an item drops with in every context, such as the "Venomcursed" label and its effect.
     * Trees with several context-free nodes can roll among them (tertiary stats, chance sockets), so
     * only trees made of a single such node count.
     *
     * @param  list<array<string, string>>  $trees  the item's ItemXBonusTree rows
     * @return list<int>
     */
    private function defaultBonusLists(array $trees): array
    {
        $lists = [];

        foreach ($trees as $row) {
            $nodes = $this->treeNodes[$row['ItemBonusTreeID']] ?? [];

            if (count($nodes) === 1 && $nodes[0]['ItemContext'] === '0' && $nodes[0]['ChildItemBonusListID'] !== '0') {
                $lists[] = (int) $nodes[0]['ChildItemBonusListID'];
            }
        }

        sort($lists);

        return array_values(array_unique($lists));
    }

    /**
     * @param  array<int, mixed>  $itemIds
     * @param  array<int, list<int>>  $defaultBonusLists
     * @return array<int, array{onUse: bool, onEquip: bool}>
     */
    private function effects(array $itemIds, array $defaultBonusLists): array
    {
        $links = [];

        foreach ($this->db2->rows('ItemXItemEffect') as $row) {
            if (isset($itemIds[$row['ItemID']])) {
                $links[$row['ItemEffectID']][] = (int) $row['ItemID'];
            }
        }

        $itemsByList = [];

        foreach ($defaultBonusLists as $itemId => $lists) {
            foreach ($lists as $list) {
                $itemsByList[$list][] = $itemId;
            }
        }

        foreach ($this->db2->rows('ItemBonus') as $bonus) {
            if ((int) $bonus['Type'] === self::BONUS_ITEM_EFFECT && isset($itemsByList[$bonus['ParentItemBonusListID']])) {
                $links[$bonus['Value_0']] = [...$links[$bonus['Value_0']] ?? [], ...$itemsByList[$bonus['ParentItemBonusListID']]];
            }
        }

        $effects = $this->db2->keyed('ItemEffect', filter: fn (array $row): bool => isset($links[$row['ID']]));
        $spellIds = array_fill_keys(array_column($effects, 'SpellID'), true);

        // Effects whose spell has no description are hidden value holders (e.g. the damage a use effect deals), not tooltip lines.
        $described = $this->db2->keyed('Spell', filter: fn (array $row): bool => isset($spellIds[$row['ID']]) && $row['Description_lang'] !== '');
        $result = [];

        foreach ($effects as $effect) {
            if (! isset($described[$effect['SpellID']])) {
                continue;
            }

            foreach ($links[$effect['ID']] as $itemId) {
                $result[$itemId] ??= ['onUse' => false, 'onEquip' => false];
                $result[$itemId]['onUse'] = $result[$itemId]['onUse'] || $effect['TriggerType'] === '0';
                $result[$itemId]['onEquip'] = $result[$itemId]['onEquip'] || $effect['TriggerType'] === '1';
            }
        }

        return $result;
    }

    /**
     * Sockets and limit category (e.g. crafted gear made Embellished) set by context-free bonus lists,
     * limited by each subtree's `InventoryTypeSlotMask`.
     *
     * @param  array<int, mixed>  $itemIds
     * @param  array<string, array<string, string>>  $sparse
     * @param  array<string, list<array<string, string>>>  $itemTrees
     * @return array<int, array{sockets: int, limitCategory: ?string}>
     */
    private function guaranteedBonuses(array $itemIds, array $sparse, array $itemTrees): array
    {
        $masks = [];

        foreach ($this->db2->rows('ItemBonusTree') as $tree) {
            $masks[$tree['ID']] = (int) $tree['InventoryTypeSlotMask'];
        }

        $listsByItem = [];

        foreach (array_keys($itemIds) as $itemId) {
            $inventoryType = (int) $sparse[$itemId]['InventoryType'];
            $lists = [];

            foreach ($itemTrees[$itemId] ?? [] as $row) {
                $this->collectContextFreeLists($row['ItemBonusTreeID'], $inventoryType, $masks, $lists);
            }

            $listsByItem[$itemId] = array_keys($lists);
        }

        $wanted = array_fill_keys(array_merge(...array_values($listsByItem) ?: [[]]), true);
        $sockets = [];
        $limitCategories = [];
        $randomRolls = [];

        foreach ($this->db2->rows('ItemBonus') as $bonus) {
            $list = $bonus['ParentItemBonusListID'];

            if (! isset($wanted[$list])) {
                continue;
            }

            match ((int) $bonus['Type']) {
                self::BONUS_SOCKET => $sockets[$list] = ($sockets[$list] ?? 0) + (int) $bonus['Value_0'],
                self::BONUS_LIMIT_CATEGORY => $limitCategories[$list] = $bonus['Value_0'],
                self::BONUS_RANDOM_ROLL_TOAST => $randomRolls[$list] = true,
                default => null,
            };
        }

        $result = [];

        foreach ($listsByItem as $itemId => $lists) {
            $result[$itemId] = ['sockets' => 0, 'limitCategory' => null];

            foreach ($lists as $list) {
                if (! isset($randomRolls[$list])) {
                    $result[$itemId]['sockets'] += $sockets[$list] ?? 0;
                    $result[$itemId]['limitCategory'] = $limitCategories[$list] ?? $result[$itemId]['limitCategory'];
                }
            }
        }

        return $result;
    }

    /**
     * @param  array<string, int>  $masks
     * @param  array<string, true>  $lists
     */
    private function collectContextFreeLists(string $tree, int $inventoryType, array $masks, array &$lists, int $depth = 0): void
    {
        $mask = $masks[$tree] ?? 0;

        if ($depth > 16 || ($mask !== 0 && ($mask & (1 << $inventoryType)) === 0)) {
            return;
        }

        foreach ($this->treeNodes[$tree] ?? [] as $node) {
            if ($node['ItemContext'] !== '0') {
                continue;
            }

            if ($node['ChildItemBonusListID'] !== '0') {
                $lists[$node['ChildItemBonusListID']] = true;
            }

            if ($node['ChildItemBonusTreeID'] !== '0') {
                $this->collectContextFreeLists($node['ChildItemBonusTreeID'], $inventoryType, $masks, $lists, $depth + 1);
            }
        }
    }

    /**
     * @param  list<Source>  $sources
     * @return list<Source>
     */
    private function sortSources(array $sources): array
    {
        $unique = [];

        foreach ($sources as $source) {
            $unique[implode(':', [$source['itemId'], $source['type'], $source['instanceId'], $source['encounterId']])] = $source;
        }

        $unique = array_values($unique);

        usort($unique, fn (array $a, array $b): int => [$a['itemId'], $a['type'], $a['instanceId'] ?? 0, $a['encounterId'] ?? 0]
            <=> [$b['itemId'], $b['type'], $b['instanceId'] ?? 0, $b['encounterId'] ?? 0]);

        return $unique;
    }
}
