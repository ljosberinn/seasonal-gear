<?php

namespace Tests\Feature;

use App\Gear\GearCatalog;
use App\Gear\GearFilter;
use App\Gear\Slot;
use Tests\Concerns\UsesGearDataset;
use Tests\TestCase;

class GearPageTest extends TestCase
{
    private const array ALL_SOURCES = ['raid', 'tier', 'dungeon', 'delve', 'world', 'crafted'];

    use UsesGearDataset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->useGearDataset();
    }

    public function test_defaults_to_raid_tier_set_and_dungeon_sources(): void
    {
        $this->get(route('gear.index'))
            ->assertOk()
            ->assertSee('Test Season')
            ->assertSee('12.1.5.70961')
            ->assertSeeInOrder(['Visor of the Predator', 'Worldroot Canopy'])
            ->assertSee('Vile Alchemist&#039;s Band', false)
            ->assertSee('Calamitous Echo')
            ->assertDontSee('Hasty Helm')
            ->assertDontSee('Greatsword of Testing')
            ->assertDontSee('Martyr');
    }

    public function test_hides_catalyst_items(): void
    {
        $this->get(route('gear.index', ['sources' => array_keys(GearCatalog::SOURCE_TYPES)]))
            ->assertOk()
            ->assertSee('Hasty Helm')
            ->assertDontSee('Catalyzed Cowl');

        $this->get(route('gear.index', ['sources' => ['catalyst']]))->assertSessionHasErrors('sources.0');
    }

    public function test_item_links_carry_the_best_upgrade_rank_by_default(): void
    {
        $this->get(route('gear.index'))
            ->assertOk()
            ->assertSee('wow.zamimg.com/js/tooltips.js', false)
            ->assertSee('item=268249?bonus=12854&amp;ilvl=334', false)
            ->assertSee('data-wowhead="bonus=13848&amp;ilvl=344"', false);
    }

    public function test_upgrade_track_picks_the_linked_rank_or_the_nearest_reachable_track(): void
    {
        $this->get(route('gear.index', ['track' => 'hero', 'sources' => self::ALL_SOURCES]))
            ->assertOk()
            ->assertSee('data-wowhead="bonus=12846&amp;ilvl=321"', false)
            ->assertDontSee('bonus=12854', false)
            ->assertDontSee('bonus=13848', false);

        // Delve gear stops at Hero, so it keeps its Hero rank when Myth is picked.
        $this->get(route('gear.index', ['track' => 'myth', 'sources' => ['delve']]))
            ->assertOk()
            ->assertSee('data-wowhead="bonus=12846&amp;ilvl=321"', false)
            ->assertDontSee('bonus=12854', false);

        $this->get(route('gear.index', ['track' => 'myth']))
            ->assertOk()
            ->assertSee('data-wowhead="bonus=13848&amp;ilvl=344"', false);
    }

    public function test_orders_by_potential_item_level_before_stat_matches(): void
    {
        $this->get(route('gear.index', ['stats' => ['crit'], 'sources' => self::ALL_SOURCES]))
            ->assertOk()
            ->assertSeeInOrder(['Aqirbane Reliquary', 'Visor of the Predator', 'Hasty Helm']);
    }

    public function test_filters_by_slot_and_all_selected_stats(): void
    {
        $this->get(route('gear.index', ['slot' => 'head', 'stats' => ['mastery', 'crit'], 'sources' => self::ALL_SOURCES]))
            ->assertOk()
            ->assertSee('Visor of the Predator')
            ->assertSee('Worldroot Canopy')
            ->assertSee('Martyr&#039;s Crown', false)
            ->assertDontSee('Vile Alchemist')
            ->assertDontSee('Hasty Helm');
    }

    public function test_filters_by_source_type(): void
    {
        $this->get(route('gear.index', ['sources' => ['raid']]))
            ->assertOk()
            ->assertSee('Vile Alchemist')
            ->assertDontSee('Visor of the Predator')
            ->assertDontSee('Martyr');
    }

    public function test_counts_each_option_against_the_other_groups_filters(): void
    {
        $counts = $this->app->make(GearCatalog::class)->counts(new GearFilter(slot: Slot::Head, stats: ['crit'], sources: ['dungeon']));

        $this->assertSame(4, $counts['anySource']);
        $this->assertSame(['dungeon' => 2, 'delve' => 1, 'crafted' => 1], array_filter($counts['sources']));
        $this->assertSame(2, $counts['anyStat']);
        $this->assertSame(['crit' => 2, 'mastery' => 2], array_filter($counts['stats']));
        $this->assertSame(2, $counts['anySlot']);
        $this->assertSame(['head' => 2], array_filter($counts['slots']));
    }

    public function test_class_route_limits_armor_type_and_class_restricted_items(): void
    {
        $this->get(route('gear.class', ['class' => 'evoker']))
            ->assertOk()
            ->assertSee('Calamitous Echo')
            ->assertSee('Vile Alchemist')
            ->assertDontSee('Worldroot Canopy')
            ->assertDontSee('Visor of the Predator');

        $this->get(route('gear.class', ['class' => 'hunter']))
            ->assertOk()
            ->assertDontSee('Calamitous Echo');
    }

    public function test_spec_route_limits_primary_stat_and_weapons(): void
    {
        $this->get(route('gear.class', ['class' => 'paladin', 'spec' => 'retribution', 'sources' => self::ALL_SOURCES]))
            ->assertOk()
            ->assertSee('Visor of the Predator')
            ->assertSee('Greatsword of Testing')
            ->assertDontSee('Staff of Testing')
            ->assertDontSee('Bulwark of Testing');

        $this->get(route('gear.class', ['class' => 'paladin', 'spec' => 'protection', 'sources' => self::ALL_SOURCES]))
            ->assertOk()
            ->assertSee('Bulwark of Testing')
            ->assertDontSee('Greatsword of Testing');
    }

    public function test_spec_override_limits_items_without_a_primary_stat(): void
    {
        $this->get(route('gear.class', ['class' => 'warrior', 'spec' => 'arms']))
            ->assertOk()
            ->assertSee('Voracious Heart');

        $this->get(route('gear.class', ['class' => 'evoker', 'spec' => 'augmentation']))
            ->assertOk()
            ->assertSee('Vile Alchemist')
            ->assertDontSee('Voracious Heart');
    }

    public function test_marks_embellished_crafted_items(): void
    {
        $this->get(route('gear.index', ['sources' => ['crafted']]))
            ->assertOk()
            ->assertSeeInOrder(['Martyr&#039;s Crown', 'Embellished'], false);

        $this->get(route('gear.index'))
            ->assertOk()
            ->assertDontSee('Embellished');
    }

    public function test_type_column_only_shows_when_it_tells_items_apart(): void
    {
        $this->get(route('gear.index'))
            ->assertOk()
            ->assertSee('>Type</th>', false)
            ->assertSee('1 Socket | On Use');

        $this->get(route('gear.index', ['slot' => 'head']))
            ->assertOk()
            ->assertDontSee('>Type</th>', false)
            ->assertDontSee('>Slot</th>', false);

        // ring, neck and trinket have no armor or weapon type
        $this->get(route('gear.index', ['sources' => ['raid']]))
            ->assertOk()
            ->assertDontSee('>Type</th>', false);
    }

    public function test_lists_classes_alphabetically(): void
    {
        $this->get(route('gear.index'))
            ->assertOk()
            ->assertSeeInOrder(['All Classes', 'Death Knight', 'Demon Hunter', 'Druid', 'Evoker', 'Hunter', 'Mage', 'Monk', 'Paladin', 'Priest', 'Rogue', 'Shaman', 'Warlock', 'Warrior']);
    }

    public function test_unknown_class_or_spec_is_not_found(): void
    {
        $this->get('/tinker')->assertNotFound();
        $this->get('/paladin/arcane')->assertNotFound();
    }

    public function test_rejects_unknown_filter_values(): void
    {
        $this->get(route('gear.index', ['stats' => ['leech']]))->assertSessionHasErrors('stats.0');
        $this->get(route('gear.index', ['slot' => 'tabard']))->assertSessionHasErrors('slot');
        $this->get(route('gear.index', ['track' => 'explorer']))->assertSessionHasErrors('track');
    }
}
