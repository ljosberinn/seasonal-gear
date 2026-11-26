<?php

namespace Tests\Feature;

use App\Gear\GearCatalog;
use App\Gear\GearFilter;
use App\Gear\Slot;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GearPageTest extends TestCase
{
    private string $datasetPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->datasetPath = tempnam(sys_get_temp_dir(), 'gear');
        File::put($this->datasetPath, json_encode($this->dataset()));
        config(['gear.data_path' => $this->datasetPath]);
    }

    protected function tearDown(): void
    {
        File::delete($this->datasetPath);

        parent::tearDown();
    }

    public function test_lists_every_item_without_filters(): void
    {
        $this->get(route('gear.index'))
            ->assertOk()
            ->assertSee('Test Season')
            ->assertSee('12.1.5.70961')
            ->assertSeeInOrder(['Visor of the Predator', 'Worldroot Canopy'])
            ->assertSee('Vile Alchemist&#039;s Band', false);
    }

    public function test_item_links_carry_wowhead_tooltip_item_level(): void
    {
        $this->get(route('gear.index'))
            ->assertOk()
            ->assertSee('wow.zamimg.com/js/tooltips.js', false)
            ->assertSee('data-wowhead="ilvl=334"', false);
    }

    public function test_filters_by_slot_and_all_selected_stats(): void
    {
        $this->get(route('gear.index', ['slot' => 'head', 'stats' => ['mastery', 'crit']]))
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
        $this->get(route('gear.class', ['class' => 'paladin', 'spec' => 'retribution']))
            ->assertOk()
            ->assertSee('Visor of the Predator')
            ->assertSee('Greatsword of Testing')
            ->assertDontSee('Staff of Testing')
            ->assertDontSee('Bulwark of Testing');

        $this->get(route('gear.class', ['class' => 'paladin', 'spec' => 'protection']))
            ->assertOk()
            ->assertSee('Bulwark of Testing')
            ->assertDontSee('Greatsword of Testing');
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
    }

    /**
     * @return array<string, mixed>
     */
    private function dataset(): array
    {
        $item = fn (int $id, string $name, string $slot, int $inventoryType, int $classId, int $subclassId, array $primaries, array $secondaries, array $extra = []): array => [
            'id' => $id, 'name' => $name, 'slot' => $slot, 'inventoryType' => $inventoryType, 'classId' => $classId,
            'subclassId' => $subclassId, 'quality' => 4, 'allowableClass' => -1, 'primaries' => $primaries,
            'secondaries' => $secondaries, 'choiceStats' => false, 'itemLevel' => 334,
            'stats' => ['sta' => 1000] + $secondaries, 'sockets' => 0, 'onUse' => false, 'onEquip' => false, 'itemSet' => null,
            ...$extra,
        ];
        $source = fn (int $itemId, string $type, ?string $instance = null): array => [
            'itemId' => $itemId, 'type' => $type, 'instanceId' => $instance ? 1 : null, 'instance' => $instance,
            'encounterId' => null, 'encounter' => null, 'itemLevel' => 334,
        ];

        return [
            'build' => '12.1.5.70961',
            'season' => ['slug' => 'test', 'name' => 'Test Season'],
            'items' => [
                $item(268249, "Vile Alchemist's Band", 'finger', 11, 4, 0, [], ['mastery' => 14350, 'crit' => 3150]),
                $item(251229, 'Visor of the Predator', 'head', 1, 4, 4, ['str', 'int'], ['crit' => 4550, 'mastery' => 2450]),
                $item(251199, 'Worldroot Canopy', 'head', 1, 4, 1, ['int'], ['mastery' => 3500, 'crit' => 3500]),
                $item(900001, 'Hasty Helm', 'head', 1, 4, 4, ['str'], ['haste' => 4000, 'crit' => 3000]),
                $item(271499, "Calamitous Echo's Sundered Peaks", 'shoulder', 3, 4, 3, ['agi', 'int'], ['crit' => 4846, 'vers' => 2154], ['allowableClass' => 4096]),
                $item(239652, "Martyr's Crown", 'head', 1, 4, 1, ['int'], [], ['choiceStats' => true, 'itemLevel' => null, 'stats' => null]),
                $item(900002, 'Greatsword of Testing', 'two-hand', 17, 2, 8, ['str'], ['haste' => 3500, 'vers' => 3500]),
                $item(900003, 'Staff of Testing', 'two-hand', 17, 2, 10, ['int'], ['haste' => 3500, 'vers' => 3500]),
                $item(900004, 'Bulwark of Testing', 'off-hand', 14, 4, 6, ['str'], ['haste' => 3500, 'vers' => 3500]),
            ],
            'sources' => [
                $source(268249, 'raid', 'The Venomous Abyss'),
                $source(251229, 'dungeon', 'Voidscar Arena'),
                $source(251199, 'dungeon', 'The Blinding Vale'),
                $source(900001, 'delve'),
                $source(271499, 'tier', 'The Venomous Abyss'),
                $source(239652, 'crafted'),
                $source(900002, 'world'),
                $source(900003, 'world'),
                $source(900004, 'delve'),
            ],
        ];
    }
}
