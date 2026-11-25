<?php

namespace Tests\Unit;

use App\Gear\Stat;
use App\Gear\StatCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatCalculatorTest extends TestCase
{
    /**
     * Expected values were read from in-game tooltips; inputs are 12.1.5.70961 DB2 rows.
     *
     * @return array<string, array{int, int, array<int, int>, int, array<string, int>}>
     */
    public static function referenceItems(): array
    {
        return [
            '268249 Vile Alchemist\'s Band, Myth 6/6' => [11, 0, [Stat::Stamina->value => 7889, Stat::Mastery->value => 14350, Stat::CriticalStrike->value => 3150], 334, ['sta' => 2199, 'mastery' => 332, 'crit' => 73]],
            '268249 Vile Alchemist\'s Band, Myth 5/6' => [11, 0, [Stat::Stamina->value => 7889, Stat::Mastery->value => 14350, Stat::CriticalStrike->value => 3150], 331, ['sta' => 2124, 'mastery' => 327, 'crit' => 72]],
            '249920 Eye of Midnight, S1 Myth 6/6' => [11, 0, [Stat::Stamina->value => 7889, Stat::Haste->value => 17500], 289, ['sta' => 1309, 'haste' => 303]],
            '249808 Litany of Lightblind Wrath' => [12, 0, [Stat::Intellect->value => 6666], 298, ['primary' => 128]],
            // DB2 lists Intellect twice (5259 + 25370); rounding them separately would give 392.
            '251111 Splitshroud Stinger' => [13, 15, [Stat::Intellect->value => 30629, Stat::Stamina->value => 7889, Stat::CriticalStrike->value => 4550, Stat::Haste->value => 2450], 298, ['primary' => 393, 'sta' => 1290, 'crit' => 56, 'haste' => 30]],
        ];
    }

    /**
     * @param  array<int, int>  $allocations
     * @param  array<string, int>  $expected
     */
    #[DataProvider('referenceItems')]
    public function test_matches_in_game_stats(int $inventoryType, int $weaponSubclass, array $allocations, int $itemLevel, array $expected): void
    {
        $this->assertEquals($expected, $this->calculator()->compute($inventoryType, $weaponSubclass, $allocations, $itemLevel));
    }

    public function test_ignores_crafted_choice_stats(): void
    {
        $stats = $this->calculator()->compute(11, 0, [Stat::Stamina->value => 7889, Stat::CraftedChoiceOne->value => 3500, Stat::CraftedChoiceTwo->value => 3500], 334);

        $this->assertSame(['sta' => 2199], $stats);
    }

    public function test_wands_use_the_one_hand_budget(): void
    {
        $this->assertSame(3, StatCalculator::budgetColumn(26, 19));
        $this->assertSame(0, StatCalculator::budgetColumn(26, 3));
    }

    private function calculator(): StatCalculator
    {
        $budget = fn (float $column0, float $column1, float $column2, float $column3): array => ['EpicF_0' => $column0, 'EpicF_1' => $column1, 'EpicF_2' => $column2, 'EpicF_3' => $column3];
        $multiplier = fn (float $other, float $jewelry): array => ['Armor Multiplier' => $other, 'Weapon Multiplier' => $other, 'Trinket Multiplier' => $other, 'Jewelry Multiplier' => $jewelry];

        return new StatCalculator(
            randPropPoints: [
                289 => $budget(235.75936889648, 176.81953430176, 132.61465454102, 117.87967681885),
                298 => $budget(256.38208007813, 192.28656005859, 144.21492004395, 128.19104003906),
                331 => $budget(348.67669677734, 261.5075378418, 196.13064575195, 174.33834838867),
                334 => $budget(358.560546875, 268.92041015625, 201.69030761719, 179.2802734375),
            ],
            staminaMultipliers: [
                289 => $multiplier(12.5072263, 12.5072263),
                298 => $multiplier(12.75308269, 12.75308269),
                331 => $multiplier(13.7292879, 13.7292879),
                334 => $multiplier(13.82240869, 13.82240869),
            ],
            combatRatingMultipliers: [
                289 => $multiplier(0.996836226, 1.30553802),
                298 => $multiplier(0.95720625, 1.281629817),
                331 => $multiplier(0.8131689, 1.161052495),
                334 => $multiplier(0.800419171, 1.148378783),
            ],
        );
    }
}
