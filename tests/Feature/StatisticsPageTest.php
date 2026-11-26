<?php

namespace Tests\Feature;

use App\Gear\GearStatistics;
use Tests\Concerns\UsesGearDataset;
use Tests\TestCase;

class StatisticsPageTest extends TestCase
{
    use UsesGearDataset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->useGearDataset();
    }

    public function test_summary_covers_only_raid_tier_set_and_dungeon_items(): void
    {
        $this->get(route('statistics.index'))
            ->assertOk()
            ->assertSee('Haste is the rarest secondary at 17% of items, Critical Strike the most common at 100%.')
            ->assertSee(route('statistics.spec', ['class' => 'paladin', 'spec' => 'protection']))
            ->assertSee(route('statistics.instance', 1320))
            ->assertSee('Voidscar Arena');

        $overall = $this->app->make(GearStatistics::class)->overall();

        // the delve, world, crafted and catalyst items stay out
        $this->assertSame(6, $overall['profile']['items']);
        $this->assertSame(['raid' => 3, 'tier' => 1, 'dungeon' => 2], $overall['bySource']);
    }

    public function test_slot_counts_link_to_the_matching_gear(): void
    {
        $allSources = ['raid', 'tier', 'dungeon', 'delve', 'world', 'crafted'];

        $this->get(route('statistics.index'))
            ->assertOk()
            ->assertSee(e(route('gear.class', ['class' => 'paladin', 'spec' => 'protection', 'slot' => 'head'])), false)
            // nothing from season content, so the link opens every source
            ->assertSee(e(route('gear.class', ['class' => 'paladin', 'spec' => 'protection', 'slot' => 'off-hand', 'sources' => $allSources])), false);

        $this->get(route('statistics.spec', ['class' => 'paladin', 'spec' => 'protection']))
            ->assertOk()
            ->assertSee(e(route('gear.class', ['class' => 'paladin', 'spec' => 'protection', 'slot' => 'head'])), false);
    }

    public function test_coverage_tells_empty_slots_from_unusable_ones(): void
    {
        $specs = collect($this->app->make(GearStatistics::class)->specs())->keyBy(fn (array $spec): string => "{$spec['classKey']}/{$spec['specKey']}");

        // the only shield is a delve drop
        $this->assertSame(0, $specs['paladin/protection']['coverage']['off-hand']);
        $this->assertNull($specs['paladin/retribution']['coverage']['off-hand']);
        $this->assertSame(1, $specs['paladin/retribution']['coverage']['head']);
        // Holy wields one-handers only, so the world-drop Intellect maul isn't a gap for it
        $this->assertNull($specs['paladin/holy']['coverage']['two-hand']);
    }

    public function test_spec_page_breaks_down_slots(): void
    {
        $this->get(route('statistics.spec', ['class' => 'paladin', 'spec' => 'protection']))
            ->assertOk()
            ->assertSee('Protection Paladin')
            ->assertSeeInOrder(['Head', 'Off Hand']);

        $this->get('/statistics/paladin/arcane')->assertNotFound();
        $this->get('/statistics/tinker/arcane')->assertNotFound();
    }

    public function test_instance_page_counts_tier_pieces_toward_the_raid(): void
    {
        $instance = $this->app->make(GearStatistics::class)->instance(1320);

        $this->assertSame('raid', $instance['type']);
        $this->assertSame(4, $instance['profile']['items']);
        $this->assertEqualsCanonicalizing(['Vashnik the Malignant', "Ula'tek", 'Tier Set'], array_column($instance['encounters'], 'name'));

        $this->get(route('statistics.instance', 1320))
            ->assertOk()
            ->assertSee('Ula&#039;tek', false)
            ->assertSee('icons/medium/inv_121_achievement_raid_ulatek.jpg', false)
            ->assertSee('icons/medium/8039569.jpg', false);

        $this->get(route('statistics.instance', 999))->assertNotFound();
    }
}
