<?php

namespace Tests\Unit;

use App\Gear\GearStatistics;
use PHPUnit\Framework\TestCase;

class GearStatisticsTest extends TestCase
{
    public function test_profile_counts_stats_and_pairs_of_items_with_fixed_secondaries(): void
    {
        $item = fn (array $secondaries, bool $choiceStats = false): array => ['secondaries' => $secondaries, 'choiceStats' => $choiceStats];

        $profile = GearStatistics::profile([
            $item(['mastery' => 3500, 'crit' => 3500]),
            $item(['haste' => 4000, 'crit' => 3000]),
            $item(['crit' => 6666]),
            $item([]),
            $item([], choiceStats: true),
        ]);

        $this->assertSame(5, $profile['items']);
        $this->assertSame(3, $profile['rated']);
        $this->assertSame(['crit' => 3, 'haste' => 1, 'mastery' => 1, 'vers' => 0], $profile['presence']);
        $this->assertSame(['crit-haste' => 1, 'crit-mastery' => 1], array_filter($profile['pairs']));
        $this->assertSame(1.0, GearStatistics::share($profile, 'crit'));
        $this->assertSame(1 / 3, GearStatistics::share($profile, 'crit-haste'));
    }

    public function test_lean_picks_the_largest_gap_to_the_overall_share_among_large_enough_instances(): void
    {
        $profile = fn (int $rated, array $presence): array => ['items' => $rated, 'rated' => $rated, 'presence' => $presence + ['crit' => 0, 'haste' => 0, 'mastery' => 0, 'vers' => 0], 'pairs' => []];
        $overall = ['profile' => $profile(100, ['crit' => 50, 'haste' => 50, 'mastery' => 50, 'vers' => 50]), 'bySource' => []];

        $highlights = GearStatistics::highlights($overall, [], [
            ['name' => 'Even Keep', 'profile' => $profile(20, ['crit' => 10, 'haste' => 10, 'mastery' => 10, 'vers' => 10])],
            ['name' => 'Versless Vault', 'profile' => $profile(20, ['crit' => 14, 'haste' => 13, 'mastery' => 12, 'vers' => 1])],
            // further off, but too small to count
            ['name' => 'Tiny Den', 'profile' => $profile(4, ['crit' => 4, 'haste' => 4])],
        ]);

        $this->assertSame('Versless Vault', $highlights['lean']['instance']['name']);
        $this->assertSame('vers', $highlights['lean']['stat']);
        $this->assertSame(0.05, $highlights['lean']['share']);
        $this->assertSame(0.5, $highlights['lean']['overall']);
    }

    public function test_pair_keys_follow_the_secondary_order(): void
    {
        $this->assertSame(['crit-haste', 'crit-mastery', 'crit-vers', 'haste-mastery', 'haste-vers', 'mastery-vers'], GearStatistics::pairKeys());
    }
}
