<?php

namespace App\Gear;

enum Slot: string
{
    case Head = 'head';
    case Neck = 'neck';
    case Shoulder = 'shoulder';
    case Back = 'back';
    case Chest = 'chest';
    case Wrist = 'wrist';
    case Hands = 'hands';
    case Waist = 'waist';
    case Legs = 'legs';
    case Feet = 'feet';
    case Finger = 'finger';
    case Trinket = 'trinket';
    case OneHand = 'one-hand';
    case TwoHand = 'two-hand';
    case MainHand = 'main-hand';
    case OffHand = 'off-hand';
    case Ranged = 'ranged';

    public static function fromInventoryType(int $inventoryType): ?self
    {
        return match ($inventoryType) {
            1 => self::Head,
            2 => self::Neck,
            3 => self::Shoulder,
            5, 20 => self::Chest,
            6 => self::Waist,
            7 => self::Legs,
            8 => self::Feet,
            9 => self::Wrist,
            10 => self::Hands,
            11 => self::Finger,
            12 => self::Trinket,
            13 => self::OneHand,
            14, 22, 23 => self::OffHand,
            15, 25, 26 => self::Ranged,
            16 => self::Back,
            17 => self::TwoHand,
            21 => self::MainHand,
            default => null,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OneHand => 'One-Hand',
            self::TwoHand => 'Two-Hand',
            self::MainHand => 'Main Hand',
            self::OffHand => 'Off Hand',
            default => ucfirst($this->value),
        };
    }

    /**
     * Equipment slot names used by exports, where every weapon that isn't held in the off hand goes in the main hand.
     */
    public function exportKey(): string
    {
        return match ($this) {
            self::OneHand, self::TwoHand, self::MainHand, self::Ranged => 'main_hand',
            self::OffHand => 'off_hand',
            default => $this->value,
        };
    }

    public function isArmor(): bool
    {
        return in_array($this, [self::Head, self::Shoulder, self::Chest, self::Wrist, self::Hands, self::Waist, self::Legs, self::Feet], true);
    }
}
