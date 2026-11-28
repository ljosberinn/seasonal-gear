<?php

namespace App\Gear;

/**
 * When an item effect happens, the first half of a Forever effect category such as "When struck: Damage".
 */
enum ForeverEffectTrigger: string
{
    case Use = 'use';
    case Equip = 'equip';
    case Struck = 'struck';
    case Hit = 'hit';
    case Cast = 'cast';
    case Kill = 'kill';

    public function label(): string
    {
        return match ($this) {
            self::Use => 'Use',
            self::Equip => 'Equip',
            self::Struck => 'When struck',
            self::Hit => 'Chance on hit',
            self::Cast => 'On spell cast',
            self::Kill => 'On kill',
        };
    }
}
