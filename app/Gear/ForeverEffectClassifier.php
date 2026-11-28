<?php

namespace App\Gear;

/**
 * Sorts item effects into categories such as "When struck: Damage" by what their spells do
 * (`SpellEffect.Effect` and `EffectAura`) and what triggers their procs (`SpellAuraOptions.ProcTypeMask_0`),
 * rather than by tooltip wording.
 */
class ForeverEffectClassifier
{
    /** `ItemEffect.TriggerType` */
    private const int TRIGGER_USE = 0;

    private const int TRIGGER_EQUIP = 1;

    private const int TRIGGER_HIT = 2;

    private const int AURA_PROC_TRIGGER_SPELL = 42;

    private const int AURA_PROC_TRIGGER_DAMAGE = 43;

    private const int AURA_DAMAGE_SHIELD = 15;

    private const int AURA_MOD_THREAT = 10;

    private const int EFFECT_THREAT = 63;

    /** ProcFlags: killing a unit that yields experience or honor. */
    private const int PROC_KILL = 0x2;

    /** ProcFlags: taking melee, ranged or spell hits. */
    private const int PROC_TAKEN = 0x8 | 0x20 | 0x80 | 0x200 | 0x800 | 0x2000 | 0x8000 | 0x20000 | 0x80000 | 0x100000;

    /** ProcFlags: dealing melee or ranged hits. */
    private const int PROC_DONE_ATTACK = 0x4 | 0x10 | 0x40 | 0x100 | 0x400000 | 0x800000;

    /** ProcFlags: casting or landing spells. */
    private const int PROC_DONE_SPELL = 0x400 | 0x1000 | 0x4000 | 0x10000 | 0x40000;

    /** `SpellEffect.Effect` values that cast another spell, which is what the effect really does. */
    private const array TRIGGER_EFFECTS = [64, 142];

    /** `SpellEffect.EffectAura` values that cast another spell periodically. */
    private const array TRIGGER_AURAS = [23];

    /** `SpellEffect.Effect` values that apply an aura. */
    private const array AURA_EFFECTS = [6, 35, 65, 119, 128, 129, 143];

    private const array EFFECT_OUTCOMES = [
        2 => ForeverEffectOutcome::Damage,
        9 => ForeverEffectOutcome::LifeSteal,
        10 => ForeverEffectOutcome::Heal,
        19 => ForeverEffectOutcome::ExtraAttacks,
        30 => ForeverEffectOutcome::RestorePower,
        5 => ForeverEffectOutcome::Teleport,
        50 => ForeverEffectOutcome::Teleport,
        252 => ForeverEffectOutcome::Teleport,
        28 => ForeverEffectOutcome::Summon,
        38 => ForeverEffectOutcome::Dispel,
        108 => ForeverEffectOutcome::Dispel,
    ];

    private const array AURA_OUTCOMES = [
        3 => ForeverEffectOutcome::DamageOverTime,
        15 => ForeverEffectOutcome::Damage,
        53 => ForeverEffectOutcome::LifeSteal,
        8 => ForeverEffectOutcome::Heal,
        20 => ForeverEffectOutcome::Heal,
        69 => ForeverEffectOutcome::Absorb,
        24 => ForeverEffectOutcome::RestorePower,
        21 => ForeverEffectOutcome::RestorePower,
        84 => ForeverEffectOutcome::Regeneration,
        85 => ForeverEffectOutcome::Regeneration,
        161 => ForeverEffectOutcome::Regeneration,
        5 => ForeverEffectOutcome::CrowdControl,
        6 => ForeverEffectOutcome::CrowdControl,
        7 => ForeverEffectOutcome::CrowdControl,
        12 => ForeverEffectOutcome::CrowdControl,
        25 => ForeverEffectOutcome::CrowdControl,
        26 => ForeverEffectOutcome::CrowdControl,
        27 => ForeverEffectOutcome::CrowdControl,
        56 => ForeverEffectOutcome::CrowdControl,
        60 => ForeverEffectOutcome::CrowdControl,
        67 => ForeverEffectOutcome::CrowdControl,
        33 => ForeverEffectOutcome::Slow,
        77 => ForeverEffectOutcome::Immunity,
        39 => ForeverEffectOutcome::Immunity,
        40 => ForeverEffectOutcome::Immunity,
        117 => ForeverEffectOutcome::CrowdControlResistance,
        31 => ForeverEffectOutcome::RunSpeed,
        129 => ForeverEffectOutcome::RunSpeed,
        58 => ForeverEffectOutcome::SwimSpeed,
        32 => ForeverEffectOutcome::MountSpeed,
        130 => ForeverEffectOutcome::MountSpeed,
        172 => ForeverEffectOutcome::MountSpeed,
        16 => ForeverEffectOutcome::Stealth,
        154 => ForeverEffectOutcome::Stealth,
        17 => ForeverEffectOutcome::StealthDetection,
        107 => ForeverEffectOutcome::AbilityModifier,
        108 => ForeverEffectOutcome::AbilityModifier,
        112 => ForeverEffectOutcome::AbilityModifier,
        30 => ForeverEffectOutcome::Skill,
        98 => ForeverEffectOutcome::Skill,
        155 => ForeverEffectOutcome::WaterBreathing,
        144 => ForeverEffectOutcome::SlowFall,
        105 => ForeverEffectOutcome::SlowFall,
        87 => ForeverEffectOutcome::DamageReduction,
        14 => ForeverEffectOutcome::DamageReduction,
        113 => ForeverEffectOutcome::DamageReduction,
    ];

    /** Auras that add stats; negative amounts weaken the target instead. */
    private const array STAT_AURAS = [13, 22, 29, 47, 49, 51, 52, 54, 55, 57, 65, 79, 99, 101, 102, 124, 131, 133, 135, 136, 137, 138, 140, 142, 168, 180, 183, 189, 250, 274, 290, 306, 319, 674];

    /** Auras whose bonus is a negative amount, like cheaper spells or ignoring resistances. */
    private const array REDUCING_STAT_AURAS = [72, 73, 123];

    /** Auras changing how long crowd control lasts; negative amounts shorten it on the wearer. */
    private const array CROWD_CONTROL_DURATION_AURAS = [232, 245];

    /** `SpellEffect.ImplicitTarget_0` values that aim at an enemy. */
    private const array ENEMY_TARGETS = [6, 15, 16, 24, 53, 77];

    /** Triggered spells can nest; real chains stay well below this. */
    private const int MAX_DEPTH = 4;

    /** @var array<int, list<array{effect: int, aura: int, points: float, triggerSpell: int, onEnemy: bool}>> by spell ID */
    private array $effects = [];

    /** @var array<int, int> ProcFlags by spell ID */
    private array $procFlags = [];

    public function __construct(Db2Directory $db2)
    {
        foreach ($db2->rows('SpellEffect') as $row) {
            if ($row['DifficultyID'] === '0') {
                $this->effects[(int) $row['SpellID']][] = [
                    'effect' => (int) $row['Effect'],
                    'aura' => (int) $row['EffectAura'],
                    'points' => (float) $row['EffectBasePointsF'],
                    'triggerSpell' => (int) $row['EffectTriggerSpell'],
                    'onEnemy' => in_array((int) $row['ImplicitTarget_0'], self::ENEMY_TARGETS, true) || in_array((int) $row['ImplicitTarget_1'], self::ENEMY_TARGETS, true),
                ];
            }
        }

        foreach ($db2->rows('SpellAuraOptions') as $row) {
            if ($row['DifficultyID'] === '0') {
                $this->procFlags[(int) $row['SpellID']] = (int) $row['ProcTypeMask_0'];
            }
        }
    }

    /**
     * Category keys (see {@see self::key()}) with the effect's comparable value, if its outcome has a unit.
     *
     * @return array<string, float|null>
     */
    public function classify(int $triggerType, int $spellId): array
    {
        $categories = [];
        $add = function (ForeverEffectTrigger $trigger, array $outcomes) use (&$categories): void {
            foreach ($outcomes as [$outcome, $value]) {
                $key = self::key($trigger, $outcome);
                $previous = $categories[$key] ?? null;
                $categories[$key] = $value === null || $outcome->unit() === null ? $previous : max($previous ?? $value, $value);
            }
        };

        if ($triggerType !== self::TRIGGER_EQUIP) {
            $add($triggerType === self::TRIGGER_HIT ? ForeverEffectTrigger::Hit : ForeverEffectTrigger::Use, $this->outcomes($spellId));
        } else {
            foreach ($this->effects[$spellId] ?? [] as $effect) {
                if ($effect['aura'] === self::AURA_PROC_TRIGGER_SPELL || $effect['aura'] === self::AURA_PROC_TRIGGER_DAMAGE) {
                    $outcomes = $effect['aura'] === self::AURA_PROC_TRIGGER_DAMAGE
                        ? [[ForeverEffectOutcome::Damage, null]]
                        : $this->outcomes($effect['triggerSpell']);

                    foreach ($this->procTriggers($spellId) as $trigger) {
                        $add($trigger, $outcomes ?: [[ForeverEffectOutcome::Other, null]]);
                    }
                } elseif ($effect['aura'] === self::AURA_DAMAGE_SHIELD) {
                    $add(ForeverEffectTrigger::Struck, [[ForeverEffectOutcome::Damage, null]]);
                } else {
                    $add(ForeverEffectTrigger::Equip, $this->effectOutcomes($effect, 0, isPassive: true));
                }
            }
        }

        if ($categories === []) {
            $add($triggerType === self::TRIGGER_USE ? ForeverEffectTrigger::Use : ($triggerType === self::TRIGGER_HIT ? ForeverEffectTrigger::Hit : ForeverEffectTrigger::Equip), [[ForeverEffectOutcome::Other, null]]);
        }

        ksort($categories);

        return $categories;
    }

    public static function key(ForeverEffectTrigger $trigger, ForeverEffectOutcome $outcome): string
    {
        return "{$trigger->value}-{$outcome->value}";
    }

    /**
     * @return array{ForeverEffectTrigger, ForeverEffectOutcome}|null
     */
    public static function parseKey(string $key): ?array
    {
        [$trigger, $outcome] = array_pad(explode('-', $key, 2), 2, '');
        $trigger = ForeverEffectTrigger::tryFrom($trigger);
        $outcome = ForeverEffectOutcome::tryFrom($outcome);

        return $trigger === null || $outcome === null ? null : [$trigger, $outcome];
    }

    /**
     * @return list<ForeverEffectTrigger>
     */
    private function procTriggers(int $spellId): array
    {
        $flags = $this->procFlags[$spellId] ?? 0;
        $triggers = [];

        if ($flags & self::PROC_TAKEN) {
            $triggers[] = ForeverEffectTrigger::Struck;
        }

        if ($flags & self::PROC_DONE_ATTACK) {
            $triggers[] = ForeverEffectTrigger::Hit;
        }

        if ($flags & self::PROC_DONE_SPELL) {
            $triggers[] = ForeverEffectTrigger::Cast;
        }

        if ($flags & self::PROC_KILL) {
            $triggers[] = ForeverEffectTrigger::Kill;
        }

        return $triggers ?: [ForeverEffectTrigger::Equip];
    }

    /**
     * @return list<array{ForeverEffectOutcome, float|null}>
     */
    private function outcomes(int $spellId, int $depth = 0): array
    {
        if ($depth > self::MAX_DEPTH) {
            return [];
        }

        $outcomes = [];

        foreach ($this->effects[$spellId] ?? [] as $effect) {
            array_push($outcomes, ...$this->effectOutcomes($effect, $depth));
        }

        return $outcomes;
    }

    /**
     * @param  array{effect: int, aura: int, points: float, triggerSpell: int, onEnemy: bool}  $effect
     * @param  bool  $isPassive  part of an equip effect rather than something it triggers
     * @return list<array{ForeverEffectOutcome, float|null}>
     */
    private function effectOutcomes(array $effect, int $depth, bool $isPassive = false): array
    {
        if (in_array($effect['effect'], self::TRIGGER_EFFECTS, true) || in_array($effect['aura'], self::TRIGGER_AURAS, true)) {
            return $this->outcomes($effect['triggerSpell'], $depth + 1);
        }

        if ($effect['effect'] === self::EFFECT_THREAT) {
            return $effect['points'] < 0 ? [[ForeverEffectOutcome::ThreatReduction, null]] : [];
        }

        if (isset(self::EFFECT_OUTCOMES[$effect['effect']])) {
            return [[self::EFFECT_OUTCOMES[$effect['effect']], null]];
        }

        if (! in_array($effect['effect'], self::AURA_EFFECTS, true)) {
            return [];
        }

        $aura = $effect['aura'];
        $points = $effect['points'];
        $onEnemy = $effect['onEnemy'];

        if (in_array($aura, self::STAT_AURAS, true) || in_array($aura, self::REDUCING_STAT_AURAS, true)) {
            $isBonus = in_array($aura, self::STAT_AURAS, true) ? $points >= 0 : $points <= 0;

            return [[$isBonus && ! $onEnemy ? ForeverEffectOutcome::Stats : ForeverEffectOutcome::Weaken, null]];
        }

        if ($aura === self::AURA_MOD_THREAT) {
            return [[$points < 0 ? ForeverEffectOutcome::ThreatReduction : ForeverEffectOutcome::ThreatIncrease, abs($points)]];
        }

        if (in_array($aura, self::CROWD_CONTROL_DURATION_AURAS, true)) {
            return match (true) {
                $points < 0 => [[ForeverEffectOutcome::CrowdControlResistance, -$points]],
                $onEnemy => [[ForeverEffectOutcome::Weaken, null]],
                default => [],
            };
        }

        $outcome = self::AURA_OUTCOMES[$aura] ?? null;

        if ($outcome === ForeverEffectOutcome::DamageReduction && $points > 0) {
            return $onEnemy ? [[ForeverEffectOutcome::Weaken, null]] : [];
        }

        // Control and slows on yourself are drawbacks (or shapeshifts), and so are negative speeds and the like.
        $isPenalty = match ($outcome) {
            null => true,
            ForeverEffectOutcome::CrowdControl, ForeverEffectOutcome::Slow => ! $onEnemy,
            ForeverEffectOutcome::DamageReduction => false,
            default => $outcome->unit() !== null && $points < 0,
        };

        if ($isPenalty) {
            return [];
        }

        if ($isPassive && $outcome === ForeverEffectOutcome::Heal) {
            $outcome = ForeverEffectOutcome::Regeneration;
        }

        return [[$outcome, $this->value($outcome, $aura, $points)]];
    }

    private function value(ForeverEffectOutcome $outcome, int $aura, float $points): ?float
    {
        return match (true) {
            $outcome->unit() === null => null,
            // Flat damage taken reductions don't compare with the percentage ones.
            $outcome === ForeverEffectOutcome::DamageReduction => $aura === 87 && $points < 0 ? -$points : null,
            // Stealth levels are stored as five points per level, like the tooltips' `${$s1/5}`.
            $outcome === ForeverEffectOutcome::Stealth, $outcome === ForeverEffectOutcome::StealthDetection => round($points / 5),
            default => $points,
        };
    }
}
