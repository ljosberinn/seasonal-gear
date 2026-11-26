<?php

namespace App\Gear;

/**
 * `ItemSparse.StatModifier_bonusStat_*` values (ItemModType) on WoW Forever items. Forever reuses
 * retired retail IDs and adds its own from 83 on; each was matched against Wowhead's Forever tooltips.
 */
enum ForeverStat: int
{
    case Agility = 3;
    case Strength = 4;
    case Intellect = 5;
    case Spirit = 6;
    case Stamina = 7;

    case Defense = 12;
    case Dodge = 13;
    case Parry = 14;
    case Block = 15;
    case BlockValue = 48;
    case Armor = 50;

    case Hit = 31;
    case Crit = 32;
    case Haste = 36;
    case Expertise = 37;
    case AttackPower = 38;
    case RangedAttackPower = 39;
    case WeaponDamage = 83;

    case SpellPower = 45;
    case Healing = 41;
    case SpellDamage = 42;
    case HolyDamage = 84;
    case FireDamage = 85;
    case NatureDamage = 86;
    case FrostDamage = 87;
    case ShadowDamage = 88;
    case ArcaneDamage = 89;
    case SpellPenetration = 47;
    case ManaRegeneration = 43;
    case HealthRegeneration = 46;

    case FireResistance = 51;
    case FrostResistance = 52;
    case ShadowResistance = 54;
    case NatureResistance = 55;
    case ArcaneResistance = 56;
    case AllResistances = 124;

    case TwoHandedAxes = 90;
    case TwoHandedMaces = 91;
    case TwoHandedSwords = 92;
    case Axes = 93;
    case Bows = 94;
    case Daggers = 96;
    case FistWeapons = 98;
    case Polearms = 101;
    case Swords = 103;

    case AttackPowerVersusHumanoids = 125;
    case AttackPowerVersusElementals = 126;
    case AttackPowerVersusDemons = 127;
    case AttackPowerVersusUndead = 128;
    case AttackPowerVersusDragonkin = 129;
    case AttackPowerVersusBeasts = 131;
    case AttackPowerVersusMechanicals = 132;
    case SpellDamageVersusHumanoids = 133;
    case SpellDamageVersusElementals = 134;
    case SpellDamageVersusDemons = 135;
    case SpellDamageVersusUndead = 136;
    case SpellDamageVersusDragonkin = 137;
    case SpellDamageVersusBeasts = 139;

    case Herbalism = 112;
    case Mining = 113;
    case Skinning = 114;
    case Cooking = 115;
    case Fishing = 117;

    public const array GROUPS = [
        'attributes' => 'Attributes',
        'defense' => 'Defense',
        'physical' => 'Physical',
        'spell' => 'Spell',
        'resistances' => 'Resistances',
        'weapon-skills' => 'Weapon Skills',
        'slaying' => 'Slaying',
        'professions' => 'Professions',
    ];

    public static function fromKey(string $key): ?self
    {
        foreach (self::cases() as $stat) {
            if ($stat->key() === $key) {
                return $stat;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(fn (self $stat): string => $stat->key(), self::cases());
    }

    public function key(): string
    {
        return match ($this) {
            self::Agility => 'agi',
            self::Strength => 'str',
            self::Intellect => 'int',
            self::Spirit => 'spi',
            self::Stamina => 'sta',
            self::AttackPower => 'ap',
            self::RangedAttackPower => 'rap',
            self::ManaRegeneration => 'mp5',
            self::HealthRegeneration => 'hp5',
            default => strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $this->name)),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Crit => 'Critical Strike',
            self::TwoHandedAxes => 'Two-Handed Axes',
            self::TwoHandedMaces => 'Two-Handed Maces',
            self::TwoHandedSwords => 'Two-Handed Swords',
            self::ManaRegeneration => 'Mana per 5 sec.',
            self::HealthRegeneration => 'Health per 5 sec.',
            self::AttackPowerVersusHumanoids => 'Attack Power vs. Humanoids',
            self::AttackPowerVersusElementals => 'Attack Power vs. Elementals',
            self::AttackPowerVersusDemons => 'Attack Power vs. Demons',
            self::AttackPowerVersusUndead => 'Attack Power vs. Undead',
            self::AttackPowerVersusDragonkin => 'Attack Power vs. Dragonkin',
            self::AttackPowerVersusBeasts => 'Attack Power vs. Beasts',
            self::AttackPowerVersusMechanicals => 'Attack Power vs. Mechanicals',
            self::SpellDamageVersusHumanoids => 'Spell Damage vs. Humanoids',
            self::SpellDamageVersusElementals => 'Spell Damage vs. Elementals',
            self::SpellDamageVersusDemons => 'Spell Damage vs. Demons',
            self::SpellDamageVersusUndead => 'Spell Damage vs. Undead',
            self::SpellDamageVersusDragonkin => 'Spell Damage vs. Dragonkin',
            self::SpellDamageVersusBeasts => 'Spell Damage vs. Beasts',
            default => (string) preg_replace('/(?<!^)[A-Z]/', ' $0', $this->name),
        };
    }

    /**
     * Shorter name for the filter chips, where the group already gives the context.
     */
    public function chipLabel(): string
    {
        return match ($this) {
            self::FireDamage, self::FireResistance => 'Fire',
            self::FrostDamage, self::FrostResistance => 'Frost',
            self::ShadowDamage, self::ShadowResistance => 'Shadow',
            self::NatureDamage, self::NatureResistance => 'Nature',
            self::ArcaneDamage, self::ArcaneResistance => 'Arcane',
            self::HolyDamage => 'Holy',
            self::AllResistances => 'All',
            self::ManaRegeneration => 'MP5',
            self::HealthRegeneration => 'HP5',
            self::AttackPowerVersusHumanoids => 'AP: Humanoids',
            self::AttackPowerVersusElementals => 'AP: Elementals',
            self::AttackPowerVersusDemons => 'AP: Demons',
            self::AttackPowerVersusUndead => 'AP: Undead',
            self::AttackPowerVersusDragonkin => 'AP: Dragonkin',
            self::AttackPowerVersusBeasts => 'AP: Beasts',
            self::AttackPowerVersusMechanicals => 'AP: Mechanicals',
            self::SpellDamageVersusHumanoids => 'Spell: Humanoids',
            self::SpellDamageVersusElementals => 'Spell: Elementals',
            self::SpellDamageVersusDemons => 'Spell: Demons',
            self::SpellDamageVersusUndead => 'Spell: Undead',
            self::SpellDamageVersusDragonkin => 'Spell: Dragonkin',
            self::SpellDamageVersusBeasts => 'Spell: Beasts',
            default => $this->label(),
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::Agility, self::Strength, self::Intellect, self::Spirit, self::Stamina => 'attributes',
            self::Defense, self::Dodge, self::Parry, self::Block, self::BlockValue, self::Armor => 'defense',
            self::Hit, self::Crit, self::Haste, self::Expertise, self::AttackPower, self::RangedAttackPower, self::WeaponDamage => 'physical',
            self::SpellPower, self::Healing, self::SpellDamage, self::HolyDamage, self::FireDamage, self::NatureDamage, self::FrostDamage,
            self::ShadowDamage, self::ArcaneDamage, self::SpellPenetration, self::ManaRegeneration, self::HealthRegeneration => 'spell',
            self::FireResistance, self::FrostResistance, self::ShadowResistance, self::NatureResistance, self::ArcaneResistance, self::AllResistances => 'resistances',
            self::TwoHandedAxes, self::TwoHandedMaces, self::TwoHandedSwords, self::Axes, self::Bows, self::Daggers, self::FistWeapons, self::Polearms, self::Swords => 'weapon-skills',
            self::Herbalism, self::Mining, self::Skinning, self::Cooking, self::Fishing => 'professions',
            default => 'slaying',
        };
    }

    /**
     * Column of the combat ratings game table that converts this stat to a percentage, if it is a rating.
     */
    public function ratingColumn(): ?string
    {
        return match ($this) {
            self::Dodge => 'Dodge',
            self::Parry => 'Parry',
            self::Block => 'Block',
            self::Hit => 'Hit - Melee',
            self::Crit => 'Crit - Melee',
            self::Haste => 'Haste - Melee',
            self::Expertise => 'Expertise',
            default => null,
        };
    }

    /**
     * @param  array<string, float>  $ratingsPerPercent  by stat key
     */
    public function format(int $value, array $ratingsPerPercent): string
    {
        $sign = $value < 0 ? '-' : '+';
        $perPercent = $ratingsPerPercent[$this->key()] ?? null;
        $amount = $perPercent === null ? (string) abs($value) : number_format(abs($value) / $perPercent, 1).'%';

        return "{$sign}{$amount} {$this->label()}";
    }
}
