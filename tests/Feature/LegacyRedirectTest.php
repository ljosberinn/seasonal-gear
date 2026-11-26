<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class LegacyRedirectTest extends TestCase
{
    public function test_home_redirects_temporarily_to_retail_with_its_query(): void
    {
        $this->get('/?slot=head&stats%5B0%5D=crit')
            ->assertFound()
            ->assertRedirect('/retail?slot=head&stats%5B0%5D=crit');
    }

    #[TestWith(['/paladin', '/retail/paladin'])]
    #[TestWith(['/death-knight/blood?slot=head', '/retail/death-knight/blood?slot=head'])]
    #[TestWith(['/statistics', '/retail/statistics'])]
    #[TestWith(['/statistics/instances/1320', '/retail/statistics/instances/1320'])]
    #[TestWith(['/statistics/mage/frost', '/retail/statistics/mage/frost'])]
    public function test_retail_pages_moved_permanently_under_retail(string $from, string $to): void
    {
        $this->get($from)
            ->assertMovedPermanently()
            ->assertRedirect($to);
    }

    public function test_unknown_root_paths_are_not_found(): void
    {
        $this->get('/tinker')->assertNotFound();
        $this->get('/statisticsx')->assertNotFound();
    }
}
