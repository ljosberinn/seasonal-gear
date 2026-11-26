<?php

namespace Tests\Unit;

use App\Gear\Db2Directory;
use App\Gear\ForeverResolver;
use PHPUnit\Framework\TestCase;
use Tests\Concerns\WritesForeverExports;

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
            [900007, 'Kept Ring', 4, 0, 3, 45, 40, 11, -1, [[7, 6428]]],
        ]);

        $this->assertSame([900007], array_column($this->resolve()['items'], 'id'));
    }

    public function test_reports_stat_ids_it_cannot_name(): void
    {
        $this->writeExports([[900001, 'Mystery Ring', 4, 0, 3, 45, 40, 11, -1, [[7, 6428], [999, 5000]]]]);

        $result = $this->resolve();

        $this->assertSame([999 => 1], $result['unknownStats']);
        $this->assertSame(['sta' => 9], $result['items'][0]['stats']);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolve(): array
    {
        return (new ForeverResolver(new Db2Directory($this->directory), 60))->resolve('1.60.1.71139');
    }
}
