<?php

namespace Tests\Unit;

use App\Gear\GearCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class GearCatalogTest extends TestCase
{
    /**
     * @return array<string, array{array<string, int>, string}>
     */
    public static function secondaries(): array
    {
        return [
            'clear lead' => [['vers' => 2154, 'crit' => 4846], 'crit > vers'],
            'equal split' => [['mastery' => 3500, 'crit' => 3500], 'mastery = crit'],
            'near split' => [['haste' => 3220, 'crit' => 3500], 'crit ≈ haste'],
            'just outside near' => [['haste' => 3115, 'crit' => 3500], 'crit > haste'],
            'single stat' => [['crit' => 143], 'crit'],
            'four stats' => [['crit' => 101, 'mastery' => 101, 'haste' => 101, 'vers' => 101], 'crit = mastery = haste = vers'],
        ];
    }

    /**
     * @param  array<string, int>  $secondaries
     */
    #[DataProvider('secondaries')]
    public function test_ranks_secondaries_by_value_with_their_relation(array $secondaries, string $expected): void
    {
        $ranked = collect(GearCatalog::rankSecondaries($secondaries))
            ->map(fn (array $entry): string => trim($entry['relation'].' '.$entry['stat']))
            ->implode(' ');

        $this->assertSame($expected, $ranked);
    }

    public function test_cloaks_have_no_armor_type(): void
    {
        $cloak = ['slot' => 'back', 'classId' => 4, 'subclassId' => 1, 'inventoryType' => 16];

        $this->assertSame('', GearCatalog::typeLabel($cloak));
        $this->assertSame('Cloth', GearCatalog::typeLabel(['slot' => 'head', 'inventoryType' => 1] + $cloak));
    }
}
