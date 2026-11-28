<?php

namespace Tests\Feature;

use Tests\Concerns\UsesForeverDataset;
use Tests\TestCase;

class ForeverPageTest extends TestCase
{
    use UsesForeverDataset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_lists_items_in_slot_tables_by_descending_item_level(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index'))
            ->assertOk()
            ->assertSee('1.60.1.71139')
            ->assertSeeInOrder(['>Head</h2>', 'Inquisition Crown', '>Hands</h2>', '>Legs</h2>', 'Dreadnaught Legplates', 'Ghostweave Leggings', '>Finger</h2>', 'Zarmok', 'Minor Channeling Ring', '>Trinket</h2>', '>Off Hand</h2>', '>Ranged</h2>'], false)
            ->assertSee('href="https://www.wowhead.com/forever/item=275000"', false)
            ->assertDontSee('>ID</th>', false)
            ->assertDontSee('Allocation');
    }

    public function test_shows_ratings_as_percentages_at_the_level_cap(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index'))
            ->assertOk()
            ->assertSee('+0.5% Hit')
            ->assertSee('+11 Block Value')
            ->assertSee('+5.0% Block')
            ->assertSee('-1.0% Parry')
            ->assertSee('+1.0% Critical Strike');
    }

    public function test_shows_effects_after_the_stats(): void
    {
        $this->useForeverDataset([
            $this->foreverItem(13243, 'Argent Defender', 'off-hand', 14, 4, 6, 62, 0, ['sta' => 11], ['effects' => [
                ['trigger' => 'equip', 'text' => 'Has a chance when struck in combat to increase your chance to block by 18% for 10 sec. (20s cooldown)'],
            ]]),
            $this->foreverItem(18820, 'Talisman of Ephemeral Power', 'trinket', 12, 4, 0, 66, 60, [], ['effects' => [
                ['trigger' => 'use', 'text' => 'Increases damage and healing done by magical spells and effects by up to 175 for 15 sec. (1 Min, 30 Sec Cooldown)'],
            ]]),
        ]);

        $this->get(route('forever.index'))
            ->assertOk()
            ->assertSeeInOrder(['Argent Defender', '+11 Stamina', 'Equip: Has a chance when struck in combat to increase your chance to block by 18% for 10 sec. (20s cooldown)'])
            ->assertSee('Use: Increases damage and healing done by magical spells and effects by up to 175 for 15 sec. (1 Min, 30 Sec Cooldown)');
    }

    public function test_class_filter_keeps_the_best_armor_type_and_trainable_weapons(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index', ['class' => 'warrior']))
            ->assertOk()
            ->assertSee('Dreadnaught Legplates')
            ->assertSee('Barrier Shield')
            ->assertSee('Zarmok')
            ->assertDontSee('Ghostweave Leggings')
            ->assertDontSee('Nether Force Wand')
            ->assertDontSee('Inquisition Crown');

        $this->get(route('forever.index', ['class' => 'mage']))
            ->assertOk()
            ->assertSee('Ghostweave Leggings')
            ->assertSee('Nether Force Wand')
            ->assertDontSee('Dreadnaught Legplates')
            ->assertDontSee('Barrier Shield');

        $this->get(route('forever.index', ['class' => 'paladin']))
            ->assertOk()
            ->assertSee('Inquisition Crown');
    }

    public function test_stat_filter_requires_every_picked_stat(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index', ['stats' => ['block-value', 'hit']]))
            ->assertOk()
            ->assertSee('Zarmok')
            ->assertDontSee('Dreadnaught Legplates')
            ->assertDontSee('Barrier Shield');
    }

    public function test_stat_penalties_do_not_match_the_stat_filter(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index', ['stats' => ['parry']]))
            ->assertOk()
            ->assertDontSee('Fletcher')
            ->assertSee('No items match these filters.');
    }

    public function test_slot_filter_shows_only_that_slot(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index', ['slot' => 'legs']))
            ->assertOk()
            ->assertSee('Dreadnaught Legplates')
            ->assertDontSee('Zarmok');
    }

    public function test_required_level_range_is_inclusive_and_counts_no_requirement_as_level_one(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index', ['min_level' => 40, 'max_level' => 50]))
            ->assertOk()
            ->assertSee('Zarmok')
            ->assertSee('Ghostweave Leggings')
            ->assertDontSee('Dreadnaught Legplates')
            ->assertDontSee('Minor Channeling Ring');

        $this->get(route('forever.index', ['min_level' => 1, 'max_level' => 1]))
            ->assertOk()
            ->assertSee('Minor Channeling Ring')
            ->assertDontSee('Zarmok');
    }

    public function test_item_level_range_is_inclusive(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index', ['min_item_level' => 55, 'max_item_level' => 75]))
            ->assertOk()
            ->assertSee('Ghostweave Leggings')
            ->assertSee('Barrier Shield')
            ->assertSee('Styleen')
            ->assertDontSee('Dreadnaught Legplates')
            ->assertDontSee('Zarmok');
    }

    public function test_level_inputs_are_bounded_except_for_the_highest_item_level(): void
    {
        $this->useForeverDataset();

        $content = $this->get(route('forever.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input [^>]*type="number"[^>]*name="max_level" min="1"\s+max="60"\s+value=""/', $content);
        $this->assertMatchesRegularExpression('/<input [^>]*type="number"[^>]*name="max_item_level" min="1"\s+value=""/', $content);

        $this->get(route('forever.index', ['min_item_level' => 0]))
            ->assertSessionHasErrors(['min_item_level' => 'The min item level field must be at least 1.']);

        $this->get(route('forever.index', ['min_item_level' => 1000]))
            ->assertOk()
            ->assertSee('No items match these filters.');
    }

    public function test_level_ranges_can_be_open_ended(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index', ['min_item_level' => 85, 'max_item_level' => '']))
            ->assertOk()
            ->assertSee('Dreadnaught Legplates')
            ->assertSee('Inquisition Crown')
            ->assertDontSee('Ghostweave Leggings');

        $this->get(route('forever.index', ['max_level' => 1]))
            ->assertOk()
            ->assertSee('Minor Channeling Ring')
            ->assertDontSee('Zarmok');
    }

    public function test_required_level_bounds_typed_the_wrong_way_round_still_form_a_range(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index', ['min_level' => 50, 'max_level' => 40]))
            ->assertOk()
            ->assertSee('Zarmok')
            ->assertSee('Ghostweave Leggings')
            ->assertDontSee('Dreadnaught Legplates');
    }

    public function test_paginates_a_hundred_items_per_page_and_keeps_the_filters(): void
    {
        $this->useForeverDataset(array_map(
            fn (int $index): array => $this->foreverItem(800000 + $index, sprintf('Filler Cloak %03d', $index), 'back', 16, 4, 1, 200 - $index, 10, ['sta' => 1]),
            range(1, 100),
        ));

        $this->get(route('forever.index', ['slot' => 'back']))
            ->assertOk()
            ->assertSee('Filler Cloak 100')
            ->assertDontSee('Page 1 of');

        $this->get(route('forever.index', ['stats' => ['sta']]))
            ->assertOk()
            ->assertSee('Filler Cloak 100')
            ->assertDontSee('Zarmok')
            ->assertSee('Page 1 of 2')
            ->assertSee('stats%5B0%5D=sta&amp;page=2', false);

        $this->get(route('forever.index', ['stats' => ['sta'], 'page' => 2]))
            ->assertOk()
            ->assertSee('Zarmok')
            ->assertDontSee('Filler Cloak');
    }

    public function test_class_cards_show_no_counts(): void
    {
        $this->useForeverDataset();

        $content = $this->get(route('forever.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#>All</span>\s?</a>#', $content);
        $this->assertMatchesRegularExpression('#>Mage</span>\s?</a>#', $content);
    }

    public function test_colors_main_stats_except_stamina(): void
    {
        $this->useForeverDataset();

        $content = $this->get(route('forever.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#class="[^"]*text-blue-600[^"]*">\+12 Intellect<#', $content);
        $this->assertMatchesRegularExpression('#class="[^"]*text-amber-800[^"]*">\+23 Strength<#', $content);
        $this->assertMatchesRegularExpression('#class="whitespace-nowrap">\+37 Stamina<#', $content);
    }

    public function test_marks_forever_as_the_current_game_version(): void
    {
        $this->useForeverDataset();

        $content = $this->get(route('forever.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#href="'.preg_quote(route('forever.index'), '#').'"\s+aria-current="page"#', $content);
    }

    public function test_hides_the_retail_only_statistics_link(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index'))
            ->assertOk()
            ->assertDontSee('href="'.route('statistics.index').'"', false);
    }

    public function test_rejects_unknown_filter_values(): void
    {
        $this->useForeverDataset();

        $this->get(route('forever.index', ['class' => 'death-knight']))->assertSessionHasErrors(['class' => 'The selected class is invalid.']);
        $this->get(route('forever.index', ['stats' => ['mastery']]))->assertSessionHasErrors(['stats.0' => 'The selected stats.0 is invalid.']);
        $this->get(route('forever.index', ['slot' => 'tabard']))->assertSessionHasErrors('slot');
        $this->get(route('forever.index', ['min_level' => 0]))->assertSessionHasErrors(['min_level' => 'The min level field must be between 1 and 60.']);
        $this->get(route('forever.index', ['max_level' => 61]))->assertSessionHasErrors('max_level');
    }
}
