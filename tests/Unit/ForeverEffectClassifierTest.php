<?php

namespace Tests\Unit;

use App\Gear\Db2Directory;
use App\Gear\ForeverEffectClassifier;
use App\Gear\ForeverEffectOutcome;
use App\Gear\ForeverEffectTrigger;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\WritesForeverExports;
use Tests\TestCase;

/**
 * Spells are 1.60.1.71139 DB2 rows, named by the item effect they back.
 */
class ForeverEffectClassifierTest extends TestCase
{
    use WritesForeverExports;

    private const int USE = 0;

    private const int EQUIP = 1;

    private const int HIT = 2;

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
     * @param  list<array<string, mixed>>  $spells  the first one is classified
     * @param  array<string, float|null>  $expected
     */
    #[DataProvider('effects')]
    public function test_classifies_effects_by_what_their_spells_do(int $triggerType, array $spells, array $expected): void
    {
        $this->writeExports([], spells: $spells);

        $this->assertSame($expected, (new ForeverEffectClassifier(new Db2Directory($this->directory)))->classify($triggerType, $spells[0]['id']));
    }

    /**
     * @return array<string, array{int, list<array<string, mixed>>, array<string, float|null>}>
     */
    public static function effects(): array
    {
        return [
            'Argent Defender: a block buff when struck' => [self::EQUIP, [
                ['id' => 17350, 'effects' => [[1, 'aura' => 42, 'triggerSpell' => 17351], [2, 'effect' => 3]], 'procFlags' => 0x28],
                ['id' => 17351, 'effects' => [[18, 'aura' => 51]]],
            ], ['struck-stats' => null]],
            'Sporid Cape: a damage shield' => [self::EQUIP, [
                ['id' => 1291698, 'effects' => [[1, 'aura' => 15], [1, 'aura' => 4]]],
            ], ['struck-damage' => null]],
            'Hand of Justice: extra attacks on melee hits' => [self::EQUIP, [
                ['id' => 15600, 'effects' => [[1, 'aura' => 42, 'triggerSpell' => 15601], [3, 'aura' => 4]], 'procFlags' => 0x14],
                ['id' => 15601, 'effects' => [[1, 'effect' => 19]]],
            ], ['hit-extra-attacks' => null]],
            'swim speed carries its percentage' => [self::EQUIP, [
                ['id' => 8747, 'effects' => [[15, 'aura' => 58]]],
            ], ['equip-swim-speed' => 15.0]],
            'stealth detection counts levels of five points' => [self::EQUIP, [
                ['id' => 12418, 'effects' => [[15, 'aura' => 17]]],
            ], ['equip-stealth-detection' => 3.0]],
            'threat reduction carries its percentage' => [self::USE, [
                ['id' => 26400, 'effects' => [[-70, 'aura' => 10]]],
            ], ['use-threat-reduction' => 70.0]],
            'a stun on the target' => [self::HIT, [
                ['id' => 56, 'effects' => [[1, 'aura' => 12, 'targets' => [6, 0]]]],
            ], ['hit-crowd-control' => null]],
            'a lowered stat on the target' => [self::HIT, [
                ['id' => 13524, 'effects' => [[-40, 'aura' => 99, 'targets' => [6, 0]]]],
            ], ['hit-weaken' => null]],
            'a penalty is no category of its own' => [self::EQUIP, [
                ['id' => 1291748, 'effects' => [[-25, 'aura' => 33]]],
            ], ['equip-other' => null]],
        ];
    }

    public function test_keys_round_trip(): void
    {
        $key = ForeverEffectClassifier::key(ForeverEffectTrigger::Struck, ForeverEffectOutcome::CrowdControlResistance);

        $this->assertSame('struck-cc-resistance', $key);
        $this->assertSame([ForeverEffectTrigger::Struck, ForeverEffectOutcome::CrowdControlResistance], ForeverEffectClassifier::parseKey($key));
        $this->assertNull(ForeverEffectClassifier::parseKey('equip-flying'));
    }
}
