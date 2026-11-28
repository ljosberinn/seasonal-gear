<?php

namespace Tests\Unit;

use App\Gear\Db2Directory;
use App\Gear\SpellDescriptions;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\WritesForeverExports;
use Tests\TestCase;

/**
 * Spells are 1.60.1.71139 DB2 rows; expected text matches Wowhead's Forever tooltips.
 */
class SpellDescriptionsTest extends TestCase
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
     * @param  list<array{id: int, description?: string, effects?: list<array{0: float, 1?: float, 2?: int}>, duration?: int, procChance?: int, procCooldown?: int}>  $spells  the first one is rendered
     */
    #[DataProvider('templates')]
    public function test_fills_in_description_variables(array $spells, string $expected): void
    {
        $this->assertSame($expected, $this->descriptions($spells)->render($spells[0]['id']));
    }

    /**
     * @return array<string, array{list<array<string, mixed>>, string}>
     */
    public static function templates(): array
    {
        return [
            'effect points and another spell\'s values' => [
                [
                    ['id' => 17350, 'description' => 'Has a chance when struck in combat to increase your chance to block by $17351s1% for $17351d. Undead that strike you are $s2 times as likely to activate this effect.', 'effects' => [[0], [2]]],
                    ['id' => 17351, 'description' => 'Chance to block increased by $s1%.', 'effects' => [[18]], 'duration' => 10000],
                ],
                'Has a chance when struck in combat to increase your chance to block by 18% for 10 sec. Undead that strike you are 2 times as likely to activate this effect.',
            ],
            'points that roll within a variance' => [
                [['id' => 29647, 'description' => 'Strike the target with a Flaming Shell for $s1 Fire damage.', 'effects' => [[38.5, 0.36363637447]]]],
                'Strike the target with a Flaming Shell for 32 to 46 Fire damage.',
            ],
            'a token followed by a letter' => [
                [['id' => 1292851, 'description' => 'Blasts a target for $s1 Nature damage. Mechanical targets take $s2x damage.', 'effects' => [[28, 0.66666668653], [2]]]],
                'Blasts a target for 19 to 37 Nature damage. Mechanical targets take 2x damage.',
            ],
            'an expression with the proc chance' => [
                [['id' => 15600, 'description' => '${$h/3}% chance on Melee hit to gain $s1 extra attack.', 'effects' => [[1], [3]], 'procChance' => 3]],
                '1% chance on Melee hit to gain 1 extra attack.',
            ],
            'an expression with decimals' => [
                [['id' => 1318954, 'description' => 'Improves your chance to get a Critical Strike with Melee and Ranged attacks by ${$s1}.1%.', 'effects' => [[1]]]],
                'Improves your chance to get a Critical Strike with Melee and Ranged attacks by 1.0%.',
            ],
            'an expression with the duration' => [
                [['id' => 1133, 'description' => 'Restores ${($s1/5)*$d} mana over $d.  Must remain seated while drinking.', 'effects' => [[249]], 'duration' => 27000]],
                'Restores 1345 mana over 27 sec. Must remain seated while drinking.',
            ],
            'a divided negative value' => [
                [['id' => 24429, 'description' => 'Reduces the cooldown of Counterspell by $/1000;s1 sec.', 'effects' => [[-2000]]]],
                'Reduces the cooldown of Counterspell by 2 sec.',
            ],
            'a condition on the reader\'s spells' => [
                [
                    ['id' => 1249118, 'description' => 'Your casts of $?s2060[Greater Heal]?s5185[Healing Touch][Greater Heal or Healing Touch] grant up to $1249119s1 increased healing.', 'effects' => [[0, 0.5]]],
                    ['id' => 1249119, 'effects' => [[22]]],
                ],
                'Your casts of Greater Heal or Healing Touch grant up to 22 increased healing.',
            ],
            'plurals and pronouns' => [
                [['id' => 14134, 'description' => 'Purifies $ghis:her; target, removing $m1 disease $leffect:effects; and $m2 poison $leffect:effects;.', 'effects' => [[1], [3]]]],
                'Purifies his target, removing 1 disease effect and 3 poison effects.',
            ],
            'another spell\'s description' => [
                [
                    ['id' => 1314143, 'description' => '$@spelldesc16939'],
                    ['id' => 16939, 'description' => 'Chance on hit to grant nearby party members $s1% increased critical strike chance.', 'effects' => [[4]]],
                ],
                'Chance on hit to grant nearby party members 4% increased critical strike chance.',
            ],
            'an effect index the spell lacks' => [
                [['id' => 1216997, 'description' => 'Reduces all damage taken from players and their pets by $s2%.', 'effects' => [[5]]]],
                'Reduces all damage taken from players and their pets by 5%.',
            ],
        ];
    }

    public function test_returns_null_without_a_description(): void
    {
        $this->assertNull($this->descriptions([['id' => 17351, 'effects' => [[18]]]])->render(17351));
    }

    public function test_reports_variables_it_cannot_fill_in(): void
    {
        $descriptions = $this->descriptions([['id' => 419417, 'description' => 'Returns you to $z.']]);

        $this->assertSame('Returns you to ?.', $descriptions->render(419417));
        $this->assertSame([419417], $descriptions->unresolvedSpells());
    }

    /**
     * @param  list<array<string, mixed>>  $spells
     */
    private function descriptions(array $spells): SpellDescriptions
    {
        $this->writeExports([], spells: $spells);

        return new SpellDescriptions(new Db2Directory($this->directory));
    }
}
