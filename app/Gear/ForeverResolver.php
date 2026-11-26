<?php

namespace App\Gear;

/**
 * Builds the WoW Forever item dataset: every Uncommon or better weapon and armor piece with stats.
 *
 * @phpstan-type ForeverItem array{id: int, name: string, quality: int, itemLevel: int, requiredLevel: int, inventoryType: int, slot: string, classId: int, subclassId: int, allowableClass: int, stats: array<string, int>}
 */
class ForeverResolver
{
    private const array ITEM_CLASSES = [2, 4];

    private const int MIN_QUALITY = 2;

    private const int MAX_STATS = 10;

    private const int NO_STAT = -1;

    /**
     * Unshipped debug items, e.g. "Test Haste Chest" or "Ring of Uber Resists (TEST)".
     */
    private const string TEST_ITEM_PATTERN = '/\btest\b/i';

    public function __construct(private readonly Db2Directory $db2, private readonly int $maxLevel) {}

    /**
     * @return array{build: string, ratingsPerPercent: array<string, float>, items: list<ForeverItem>, unknownStats: array<int, int>}
     */
    public function resolve(string $build): array
    {
        $itemClasses = $this->db2->keyed('Item', filter: fn (array $row): bool => in_array((int) $row['ClassID'], self::ITEM_CLASSES, true));
        $points = $this->db2->keyed('RandPropPoints');
        $statOrder = array_flip(array_column(ForeverStat::cases(), 'value'));
        $items = [];
        $unknownStats = [];

        foreach ($this->db2->rows('ItemSparse') as $row) {
            $item = $itemClasses[$row['ID']] ?? null;
            $quality = (int) $row['OverallQualityID'];
            $slot = Slot::fromInventoryType((int) $row['InventoryType']);

            // Requirements above the level cap only show up on placeholder items.
            if ($item === null || $slot === null || $quality < self::MIN_QUALITY || (int) $row['RequiredLevel'] > $this->maxLevel
                || preg_match(self::TEST_ITEM_PATTERN, $row['Display_lang'])) {
                continue;
            }

            $budget = (int) ($points[$row['ItemLevel']][$this->budgetColumn($quality, (int) $row['InventoryType'])] ?? 0);
            $allocations = [];

            for ($index = 0; $index < self::MAX_STATS; $index++) {
                $type = (int) $row["StatModifier_bonusStat_{$index}"];
                $allocation = (int) $row["StatPercentEditor_{$index}"];

                if ($type !== self::NO_STAT && $allocation !== 0) {
                    $allocations[$type] = ($allocations[$type] ?? 0) + $allocation;
                }
            }

            $stats = [];

            foreach ($allocations as $type => $allocation) {
                $stat = ForeverStat::tryFrom($type);

                if ($stat === null) {
                    $unknownStats[$type] = ($unknownStats[$type] ?? 0) + 1;

                    continue;
                }

                // A stat listed twice is rounded once, like StatCalculator does for retail.
                $value = (int) round($allocation * $budget / 10000);

                if ($value !== 0) {
                    $stats[$stat->value] = $value;
                }
            }

            if ($stats === []) {
                continue;
            }

            uksort($stats, fn (int $a, int $b): int => $statOrder[$a] <=> $statOrder[$b]);

            $items[] = [
                'id' => (int) $row['ID'],
                'name' => $row['Display_lang'],
                'quality' => $quality,
                'itemLevel' => (int) $row['ItemLevel'],
                'requiredLevel' => (int) $row['RequiredLevel'],
                'inventoryType' => (int) $row['InventoryType'],
                'slot' => $slot->value,
                'classId' => (int) $item['ClassID'],
                'subclassId' => (int) $item['SubclassID'],
                'allowableClass' => (int) $row['AllowableClass'],
                'stats' => array_combine(array_map(fn (int $type): string => ForeverStat::from($type)->key(), array_keys($stats)), $stats),
            ];
        }

        usort($items, fn (array $a, array $b): int => $a['id'] <=> $b['id']);
        ksort($unknownStats);

        return [
            'build' => $build,
            'ratingsPerPercent' => $this->ratingsPerPercent(),
            'items' => $items,
            'unknownStats' => $unknownStats,
        ];
    }

    /**
     * `RandPropPoints` column holding the stat budget for an item's quality and slot.
     */
    private function budgetColumn(int $quality, int $inventoryType): string
    {
        $tier = match (true) {
            $quality >= 4 => 'Epic',
            $quality === 3 => 'Superior',
            default => 'Good',
        };

        $slotGroup = match ($inventoryType) {
            1, 5, 7, 17, 20 => 0,
            3, 6, 8, 10, 12 => 1,
            2, 9, 11, 14, 16, 22, 23 => 2,
            13, 21 => 3,
            default => 4,
        };

        return "{$tier}_{$slotGroup}";
    }

    /**
     * @return array<string, float> rating points per percent at the level cap, by stat key
     */
    private function ratingsPerPercent(): array
    {
        $ratings = $this->db2->gameTable('combat_ratings_by_level')[$this->maxLevel];
        $result = [];

        foreach (ForeverStat::cases() as $stat) {
            if ($stat->ratingColumn() !== null) {
                $result[$stat->key()] = $ratings[$stat->ratingColumn()];
            }
        }

        return $result;
    }
}
