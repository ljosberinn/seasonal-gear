<?php

namespace App\Gear;

/**
 * What an item effect does, the second half of a Forever effect category such as "When struck: Damage".
 */
enum ForeverEffectOutcome: string
{
    case Damage = 'damage';
    case DamageOverTime = 'dot';
    case LifeSteal = 'life-steal';
    case Heal = 'heal';
    case Absorb = 'absorb';
    case RestorePower = 'restore-power';
    case Regeneration = 'regeneration';
    case ExtraAttacks = 'extra-attacks';
    case Stats = 'stats';
    case DamageReduction = 'damage-reduction';
    case Weaken = 'weaken';
    case Slow = 'slow';
    case CrowdControl = 'crowd-control';
    case Immunity = 'immunity';
    case CrowdControlResistance = 'cc-resistance';
    case RunSpeed = 'run-speed';
    case SwimSpeed = 'swim-speed';
    case MountSpeed = 'mount-speed';
    case Stealth = 'stealth';
    case StealthDetection = 'stealth-detection';
    case ThreatReduction = 'threat-reduction';
    case ThreatIncrease = 'threat-increase';
    case AbilityModifier = 'ability-modifier';
    case Skill = 'skill';
    case WaterBreathing = 'water-breathing';
    case SlowFall = 'slow-fall';
    case Summon = 'summon';
    case Teleport = 'teleport';
    case Dispel = 'dispel';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DamageOverTime => 'Damage over time',
            self::RestorePower => 'Restore mana, rage or energy',
            self::Regeneration => 'Health or mana regeneration',
            self::Stats => 'Stat bonus',
            self::Weaken => 'Weaken target',
            self::CrowdControl => 'Stun, fear or other control',
            self::Immunity => 'Immunity',
            self::CrowdControlResistance => 'Crowd control resistance',
            self::AbilityModifier => 'Class ability modifier',
            self::Skill => 'Skill bonus',
            self::Dispel => 'Remove effects',
            default => ucfirst(strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $this->name))),
        };
    }

    /**
     * Unit of the comparable value carried by effects of this kind, or null when they have none,
     * since e.g. a proc's damage means little without its proc rate.
     */
    public function unit(): ?string
    {
        return match ($this) {
            self::RunSpeed, self::SwimSpeed, self::MountSpeed, self::CrowdControlResistance,
            self::ThreatReduction, self::ThreatIncrease, self::WaterBreathing, self::DamageReduction => '%',
            self::Stealth, self::StealthDetection => ' levels',
            self::Skill => '',
            default => null,
        };
    }
}
