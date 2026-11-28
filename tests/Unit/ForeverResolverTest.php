<?php

namespace Tests\Unit;

use App\Gear\Db2Directory;
use App\Gear\ForeverResolver;
use Tests\Concerns\WritesForeverExports;
use Tests\TestCase;

class ForeverResolverTest extends TestCase
{
    use WritesForeverExports;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createForeverExportsDirectory();
    }

    protected function tearDown(): void
    {
        $this->removeForeverExports();

        parent::tearDown();
    }

    /**
     * Inputs are 1.60.1.71139 DB2 rows; expected values were read from Wowhead's Forever tooltips.
     */
    public function test_computes_stats_from_the_slot_and_quality_budget(): void
    {
        $this->writeExports([
            [275000, "Zarmok's Plated Signet", 4, 0, 3, 45, 40, 11, -1, [[48, 7857], [7, 6428], [31, 3571]]],
            [11307, 'Massive Longbow', 2, 2, 2, 47, 42, 15, -1, [[48, 15000]]],
            [19431, "Styleen's Impeding Scarab", 4, 0, 4, 75, 60, 12, -1, [[15, 6250], [48, 6000], [12, 3250]]],
            [22417, 'Dreadnaught Legplates', 4, 4, 4, 88, 60, 7, -1, [[4, 3966], [7, 6379], [12, 2241], [13, 2069], [48, 5517]]],
        ]);

        $items = collect($this->resolve()['items'])->keyBy('id');

        $this->assertSame(['sta' => 9, 'block-value' => 11, 'hit' => 5], $items[275000]['stats']);
        $this->assertSame(['block-value' => 9], $items[11307]['stats']);
        $this->assertSame(['defense' => 13, 'block' => 25, 'block-value' => 24], $items[19431]['stats']);
        $this->assertSame(['str' => 23, 'sta' => 37, 'defense' => 13, 'dodge' => 12, 'block-value' => 32], $items[22417]['stats']);
        $this->assertSame('finger', $items[275000]['slot']);
    }

    public function test_reads_rating_conversions_at_the_level_cap(): void
    {
        $this->writeExports([]);

        $this->assertSame(['dodge' => 12.0, 'parry' => 15.0, 'block' => 5.0, 'hit' => 10.0, 'crit' => 14.0, 'haste' => 10.0, 'expertise' => 10.0], $this->resolve()['ratingsPerPercent']);
    }

    public function test_rounds_a_stat_listed_twice_once(): void
    {
        // 2 × 1786 × 14 / 10000 = 5.0; rounding each 2.5 separately would give 6.
        $this->writeExports([[900001, 'Doubled Ring', 4, 0, 3, 45, 40, 11, -1, [[7, 1786], [7, 1786]]]]);

        $this->assertSame(['sta' => 5], $this->resolve()['items'][0]['stats']);
    }

    public function test_skips_items_nobody_can_use_or_that_lack_stats(): void
    {
        $this->writeExports([
            [900001, 'Poor Ring', 4, 0, 0, 45, 40, 11, -1, [[7, 6428]]],
            [900002, 'Test Haste Chest', 4, 4, 4, 45, 40, 5, -1, [[36, 6428]]],
            [900003, 'Ring of Uber Resists (TEST)', 4, 0, 4, 45, 40, 11, -1, [[55, 6428]]],
            [900004, 'Placeholder Ring', 4, 0, 3, 45, 70, 11, -1, [[7, 6428]]],
            [900005, 'Plain Ring', 4, 0, 3, 45, 40, 11, -1, []],
            [900006, 'Fancy Shirt', 4, 0, 3, 45, 40, 4, -1, [[7, 6428]]],
            [900007, 'Flowing Ritual Robes DEPRECATED', 4, 0, 3, 45, 40, 20, -1, [[7, 6428]]],
            [900008, 'Deprecated Silver-thread Cowl', 4, 0, 3, 45, 40, 1, -1, [[7, 6428]]],
            [900009, '90 Epic Frost Robes', 4, 0, 4, 45, 40, 20, -1, [[7, 6428]]],
            [900010, 'Kept Ring', 4, 0, 3, 45, 40, 11, -1, [[7, 6428]]],
        ]);

        $this->assertSame([900010], array_column($this->resolve()['items'], 'id'));
    }

    public function test_reports_stat_ids_it_cannot_name(): void
    {
        $this->writeExports([[900001, 'Mystery Ring', 4, 0, 3, 45, 40, 11, -1, [[7, 6428], [999, 5000]]]]);

        $result = $this->resolve();

        $this->assertSame([999 => 1], $result['unknownStats']);
        $this->assertSame(['sta' => 9], $result['items'][0]['stats']);
    }

    /**
     * Wowhead's tooltips read e.g. "Equip: Has a chance when struck in combat to increase your chance to block by 18%…".
     */
    public function test_adds_use_equip_and_chance_on_hit_lines_with_their_cooldowns(): void
    {
        $this->writeExports(
            [
                [13243, 'Argent Defender', 4, 6, 3, 45, 0, 14, -1, [[7, 6428]]],
                [18820, 'Talisman of Ephemeral Power', 4, 0, 3, 45, 60, 12, -1, [[7, 6428]]],
                [12798, 'Annihilator', 2, 0, 3, 45, 58, 13, -1, [[7, 6428]]],
            ],
            [[13243, 1, 17350, -1], [18820, 0, 23271, 90000], [12798, 2, 16928, -1]],
            [
                ['id' => 17350, 'description' => 'Has a chance when struck in combat to increase your chance to block by $17351s1% for $17351d.', 'procCooldown' => 20000],
                ['id' => 17351, 'effects' => [[18]], 'duration' => 10000],
                ['id' => 23271, 'description' => 'Increases damage and healing done by magical spells and effects by up to $s1 for $d.', 'effects' => [[175]], 'duration' => 15000],
                ['id' => 16928, 'description' => 'Reduces an enemy\'s armor by $s1. Stacks up to 3 times.', 'effects' => [[200]]],
            ],
        );

        $effects = collect($this->resolve()['items'])->pluck('effects', 'id');

        $this->assertSame([['trigger' => 'equip', 'text' => 'Has a chance when struck in combat to increase your chance to block by 18% for 10 sec. (20s cooldown)']], $effects[13243]);
        $this->assertSame([['trigger' => 'use', 'text' => 'Increases damage and healing done by magical spells and effects by up to 175 for 15 sec. (1 Min, 30 Sec Cooldown)']], $effects[18820]);
        $this->assertSame([['trigger' => 'hit', 'text' => 'Reduces an enemy\'s armor by 200. Stacks up to 3 times.']], $effects[12798]);
    }

    public function test_keeps_items_whose_only_bonus_is_an_effect(): void
    {
        $this->writeExports(
            [
                [6629, 'Sporid Cape', 4, 1, 3, 45, 17, 16, -1, []],
                [900001, 'Rune Bracers', 4, 1, 3, 45, 17, 9, -1, []],
                [900002, 'Silent Bracers', 4, 1, 3, 45, 17, 9, -1, []],
            ],
            [[6629, 1, 1291698, -1], [900001, 6, 900010, -1], [900002, 1, 900011, -1]],
            [
                ['id' => 1291698, 'description' => 'When struck in combat, inflicts $s2 Nature damage to the attacker.', 'effects' => [[1], [1]]],
                ['id' => 900010, 'description' => 'Engrave your bracers with a rune.'],
                ['id' => 900011, 'effects' => [[50]]],
            ],
        );

        $items = $this->resolve()['items'];

        $this->assertSame([6629], array_column($items, 'id'));
        $this->assertSame([], $items[0]['stats']);
        $this->assertSame([['trigger' => 'equip', 'text' => 'When struck in combat, inflicts 1 Nature damage to the attacker.']], $items[0]['effects']);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolve(): array
    {
        return (new ForeverResolver(new Db2Directory($this->directory), 60))->resolve('1.60.1.71139');
    }
}
