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

    public function test_pair_keys_follow_the_secondary_order(): void
    {
        $this->assertSame(['crit-haste', 'crit-mastery', 'crit-vers', 'haste-mastery', 'haste-vers', 'mastery-vers'], GearStatistics::pairKeys());
    }
}
