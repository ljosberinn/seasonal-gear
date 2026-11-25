<?php

namespace App\Gear;

use RuntimeException;

/**
 * Item stat formula from docs/dbc-current-season.md: allocations of the same stat are summed,
 * scaled by the slot budget, multiplied by the game table, and rounded once at the end.
 */
class StatCalculator
{
    private const int WEAPON_SUBCLASS_WAND = 19;

    /**
     * @param  array<int, array<string, string|float>>  $randPropPoints  keyed by item level
     * @param  array<int, array<string, float>>  $staminaMultipliers  keyed by item level
     * @param  array<int, array<string, float>>  $combatRatingMultipliers  keyed by item level
     */
    public function __construct(
        private readonly array $randPropPoints,
        private readonly array $staminaMultipliers,
        private readonly array $combatRatingMultipliers,
    ) {}

    public static function fromDirectory(Db2Directory $db2): self
    {
        $randPropPoints = [];

        foreach ($db2->rows('RandPropPoints') as $row) {
            $randPropPoints[(int) $row['ID']] = $row;
        }

        return new self($randPropPoints, $db2->gameTable('stamina'), $db2->gameTable('combat_ratings'));
    }

    /**
     * @param  array<int, int>  $allocations  Stat value => summed `StatPercentEditor`
     * @return array<string, int> stat key => value; primaries collapse into `primary`
     */
    public function compute(int $inventoryType, int $weaponSubclass, array $allocations, int $itemLevel): array
    {
        $budget = (float) ($this->randPropPoints[$itemLevel]['EpicF_'.self::budgetColumn($inventoryType, $weaponSubclass)]
            ?? throw new RuntimeException("No RandPropPoints row for item level {$itemLevel}"));
        $column = self::multiplierColumn($inventoryType);
        $summed = [];

        foreach ($allocations as $statId => $allocation) {
            $stat = Stat::tryFrom($statId);

            if ($stat === null || $stat->key() === 'choice') {
                continue;
            }

            $summed[$stat->key()] = ($summed[$stat->key()] ?? 0) + $allocation;
        }

        $result = [];

        foreach ($summed as $key => $allocation) {
            $raw = $budget * $allocation / 10000;

            $result[$key] = (int) round(match ($key) {
                'primary' => $raw,
                'sta' => $raw * $this->multiplier($this->staminaMultipliers, $itemLevel, $column),
                default => $raw * $this->multiplier($this->combatRatingMultipliers, $itemLevel, $column),
            });
        }

        return $result;
    }

    public static function budgetColumn(int $inventoryType, int $weaponSubclass): int
    {
        return match ($inventoryType) {
            1, 5, 20, 7, 17, 15 => 0,
            26 => $weaponSubclass === self::WEAPON_SUBCLASS_WAND ? 3 : 0,
            3, 10, 6, 8, 12 => 1,
            2, 11, 16, 9, 14, 23 => 2,
            13, 21, 22 => 3,
            default => throw new RuntimeException("No stat budget for inventory type {$inventoryType}"),
        };
    }

    public static function multiplierColumn(int $inventoryType): string
    {
        return match ($inventoryType) {
            2, 11 => 'Jewelry Multiplier',
            12 => 'Trinket Multiplier',
            13, 15, 17, 21, 22, 26 => 'Weapon Multiplier',
            default => 'Armor Multiplier',
        };
    }

    /**
     * @param  array<int, array<string, float>>  $table
     */
    private function multiplier(array $table, int $itemLevel, string $column): float
    {
        return $table[$itemLevel][$column] ?? throw new RuntimeException("No {$column} for item level {$itemLevel}");
    }
}
