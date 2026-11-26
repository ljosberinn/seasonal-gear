<?php

namespace App\Gear;

/**
 * `ItemSparse.StatModifier_bonusStat_*` values (ItemModType) relevant to current gear.
 */
enum Stat: int
{
    case Agility = 3;
    case Strength = 4;
    case Intellect = 5;
    case Stamina = 7;
    case CriticalStrike = 32;
    case Haste = 36;
    case Versatility = 40;
    case Mastery = 49;
    case AgilityStrengthIntellect = 71;
    case AgilityStrength = 72;
    case AgilityIntellect = 73;
    case StrengthIntellect = 74;

    // Crafted gear: the player picks these two secondaries via reagents.
    case CraftedChoiceOne = 24;
    case CraftedChoiceTwo = 25;

    public const array SECONDARY_KEYS = ['crit', 'haste', 'mastery', 'vers'];

    public function key(): string
    {
        return match ($this) {
            self::Agility, self::AgilityStrengthIntellect, self::AgilityStrength, self::AgilityIntellect,
            self::Strength, self::StrengthIntellect, self::Intellect => 'primary',
            self::Stamina => 'sta',
            self::CriticalStrike => 'crit',
            self::Haste => 'haste',
            self::Versatility => 'vers',
            self::Mastery => 'mastery',
            self::CraftedChoiceOne, self::CraftedChoiceTwo => 'choice',
        };
    }

    /**
     * Primary stats this modifier grants; hybrids resolve to whichever the wearer's spec uses.
     *
     * @return list<string>
     */
    public function primaries(): array
    {
        return match ($this) {
            self::Agility => ['agi'],
            self::Strength => ['str'],
            self::Intellect => ['int'],
            self::AgilityStrengthIntellect => ['agi', 'str', 'int'],
            self::AgilityStrength => ['agi', 'str'],
            self::AgilityIntellect => ['agi', 'int'],
            self::StrengthIntellect => ['str', 'int'],
            default => [],
        };
    }
}
