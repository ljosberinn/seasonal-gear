<?php

namespace App\Gear;

/**
 * Builds the WoW Forever item dataset: every Uncommon or better weapon and armor piece with stats or effects.
 *
 * @phpstan-type ForeverEffect array{trigger: string, text: string}
 * @phpstan-type ForeverItem array{id: int, name: string, quality: int, itemLevel: int, requiredLevel: int, inventoryType: int, slot: string, classId: int, subclassId: int, allowableClass: int, stats: array<string, int>, effects: list<ForeverEffect>}
 */
class ForeverResolver
{
    private const array ITEM_CLASSES = [2, 4];

    private const int MIN_QUALITY = 2;

    private const int MAX_STATS = 10;

    private const int NO_STAT = -1;

    /** `ItemEffect.TriggerType` values that show on the tooltip, e.g. not the rune an item teaches. */
    private const array EFFECT_TRIGGERS = [0 => 'use', 1 => 'equip', 2 => 'hit'];

    /**
     * Unshipped debug and placeholder items, e.g. "Test Haste Chest", "Ring of Uber Resists (TEST)",
     * "Flowing Ritual Robes DEPRECATED" or "90 Epic Frost Robes".
     */
    private const string PLACEHOLDER_ITEM_PATTERN = '/\btest\b|\bdeprecated\b|^\d+ /i';

    public function __construct(private readonly Db2Directory $db2, private readonly int $maxLevel) {}

    /**
     * @return array{build: string, hotfixes: array<string, string>, ratingsPerPercent: array<string, float>, items: list<ForeverItem>, unknownStats: array<int, int>, unresolvedSpells: list<int>}
     */
    public function resolve(string $build): array
    {
        $itemClasses = $this->db2->keyed('Item', filter: fn (array $row): bool => in_array((int) $row['ClassID'], self::ITEM_CLASSES, true));
        $spells = new SpellDescriptions($this->db2);
        $effects = $this->effects($itemClasses, $spells);
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
                || preg_match(self::PLACEHOLDER_ITEM_PATTERN, $row['Display_lang'])) {
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

            $itemEffects = $effects[$row['ID']] ?? [];

            if ($stats === [] && $itemEffects === []) {
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
                'effects' => $itemEffects,
            ];
        }

        usort($items, fn (array $a, array $b): int => $a['id'] <=> $b['id']);
        ksort($unknownStats);

        return [
            'build' => $build,
            'hotfixes' => $this->db2->hotfixes(),
            'ratingsPerPercent' => $this->ratingsPerPercent(),
            'items' => $items,
            'unknownStats' => $unknownStats,
            'unresolvedSpells' => $spells->unresolvedSpells(),
        ];
    }

    /**
     * Tooltip lines of each item's use, equip and chance on hit effects, in tooltip order.
     *
     * @param  array<string, array<string, string>>  $items  by item ID
     * @return array<string, list<ForeverEffect>> by item ID
     */
    private function effects(array $items, SpellDescriptions $spells): array
    {
        $links = [];

        foreach ($this->db2->rows('ItemXItemEffect') as $row) {
            if (isset($items[$row['ItemID']])) {
                $links[$row['ItemEffectID']][] = $row['ItemID'];
            }
        }

        $itemEffects = $this->db2->keyed('ItemEffect', filter: fn (array $row): bool => isset($links[$row['ID']], self::EFFECT_TRIGGERS[(int) $row['TriggerType']]));
        uasort($itemEffects, fn (array $a, array $b): int => [(int) $a['LegacySlotIndex'], (int) $a['ID']] <=> [(int) $b['LegacySlotIndex'], (int) $b['ID']]);
        $result = [];

        foreach ($itemEffects as $effect) {
            $spellId = (int) $effect['SpellID'];
            $trigger = self::EFFECT_TRIGGERS[(int) $effect['TriggerType']];
            $text = $spells->render($spellId);

            // Spells without a description only carry values for other spells, like the damage a use effect deals.
            if ($text === null || $text === '') {
                continue;
            }

            $cooldown = match ($trigger) {
                'use' => $this->formatUseCooldown(max(0, (int) $effect['CoolDownMSec']) ?: max(0, (int) $effect['CategoryCoolDownMSec'])),
                'equip', 'hit' => $spells->mentionsProcCooldown($spellId) ? '' : $this->formatProcCooldown($spells->procCooldown($spellId)),
            };

            foreach ($links[$effect['ID']] as $itemId) {
                $result[$itemId][] = ['trigger' => $trigger, 'text' => trim("{$text} {$cooldown}")];
            }
        }

        return $result;
    }

    /**
     * The game's wording, e.g. "(1 Min, 30 Sec Cooldown)".
     */
    private function formatUseCooldown(int $milliseconds): string
    {
        $seconds = intdiv($milliseconds, 1000);
        $parts = [];

        foreach (['Day' => 86400, 'Hour' => 3600, 'Min' => 60, 'Sec' => 1] as $unit => $length) {
            $count = intdiv($seconds, $length);
            $seconds %= $length;

            if ($count > 0) {
                $parts[] = $count.' '.($count > 1 && in_array($unit, ['Day', 'Hour'], true) ? "{$unit}s" : $unit);
            }
        }

        return $parts === [] ? '' : '('.implode(', ', $parts).' Cooldown)';
    }

    /**
     * The game doesn't show internal cooldowns; this is Wowhead's wording, e.g. "(20s cooldown)".
     * Sub-second ones only stop a proc from triggering itself, so they're left out.
     */
    private function formatProcCooldown(int $milliseconds): string
    {
        return $milliseconds < 1000 ? '' : '('.rtrim(rtrim(number_format($milliseconds / 1000, 1, '.', ''), '0'), '.').'s cooldown)';
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
