<?php

namespace App\Gear;

use RuntimeException;

/**
 * Evaluates the binary `WorldStateExpression.Expression` blob. Layout follows TrinityCore's
 * `ConditionMgr::IsMeetingWorldStateExpression`; only function 38 ("TimeEvent passed") is
 * supported because it is the only one journal loot gates use.
 */
class WorldStateExpression
{
    private const int FUNCTION_TIME_EVENT_PASSED = 38;

    private string $bytes;

    private int $offset = 0;

    /**
     * @param  callable(int): bool  $timeEventPassed
     */
    private function __construct(string $hex, private $timeEventPassed)
    {
        $this->bytes = (string) hex2bin($hex);
    }

    /**
     * @param  callable(int): bool  $timeEventPassed
     */
    public static function evaluate(string $hex, callable $timeEventPassed): bool
    {
        return (new self($hex, $timeEventPassed))->run();
    }

    private function run(): bool
    {
        if ($this->u8() === 0) {
            return true;
        }

        $result = $this->relationalOperation();

        while ($this->offset < strlen($this->bytes)) {
            $logic = $this->u8();

            if ($logic === 0) {
                break;
            }

            $right = $this->relationalOperation();

            $result = match ($logic) {
                1 => $result && $right,
                2 => $result || $right,
                3 => $result !== $right,
                default => throw new RuntimeException("Unknown logic operator {$logic}"),
            };
        }

        return $result;
    }

    private function relationalOperation(): bool
    {
        $left = $this->value();
        $comparison = $this->u8();

        if ($comparison === 0) {
            return $left !== 0;
        }

        $right = $this->value();

        return match ($comparison) {
            1 => $left === $right,
            2 => $left !== $right,
            3 => $left < $right,
            4 => $left <= $right,
            5 => $left > $right,
            6 => $left >= $right,
            default => throw new RuntimeException("Unknown comparison {$comparison}"),
        };
    }

    private function value(): int
    {
        $left = $this->single();
        $operator = $this->u8();

        if ($operator === 0) {
            return $left;
        }

        $right = $this->single();

        return match ($operator) {
            1 => $left + $right,
            2 => $left - $right,
            3 => $left * $right,
            4 => $right === 0 ? 0 : intdiv($left, $right),
            5 => $right === 0 ? 0 : $left % $right,
            default => throw new RuntimeException("Unknown arithmetic operator {$operator}"),
        };
    }

    private function single(): int
    {
        $type = $this->u8();

        switch ($type) {
            case 1:
                return $this->i32();
            case 2:
                $this->i32();

                // World state values are server-side; journal gates never use them.
                return 0;
            case 3:
                $function = $this->i32();
                $argument = $this->single();
                $this->single();

                if ($function !== self::FUNCTION_TIME_EVENT_PASSED) {
                    throw new RuntimeException("Unsupported world state function {$function}");
                }

                return ($this->timeEventPassed)($argument) ? 1 : 0;
            default:
                return 0;
        }
    }

    private function u8(): int
    {
        return ord($this->bytes[$this->offset++] ?? "\0");
    }

    private function i32(): int
    {
        $value = (int) unpack('V', substr($this->bytes, $this->offset, 4))[1];
        $this->offset += 4;

        return $value >= 0x80000000 ? $value - 0x100000000 : $value;
    }
}
