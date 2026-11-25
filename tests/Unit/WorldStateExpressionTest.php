<?php

namespace Tests\Unit;

use App\Gear\WorldStateExpression;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WorldStateExpressionTest extends TestCase
{
    /**
     * Real expressions from 12.1.5, evaluated inside the Season 2 window (TE 1552 until TE 2771).
     *
     * @return array<string, array{string, bool}>
     */
    public static function seasonTwoGates(): array
    {
        return [
            '52104 TE(2771) == 0, S2 legacy remakes' => ['01032600000001D30A000000000101000000000000', true],
            '52105 TE(2771) == 1, originals after S2' => ['01032600000001D30A000000000101010000000000', false],
            '50187 TE(1552) == 1' => ['010326000000011006000000000101010000000000', true],
            '50188 TE(1552) == 0' => ['010326000000011006000000000101000000000000', false],
            '50899 TE(1525) == 1 AND TE(1552) == 0, S1 only' => ['01032600000001F5050000000001010100000000010326000000011006000000000101000000000000', false],
            '51455 TE(1867) == 1 AND TE(2771) == 0' => ['010326000000014B07000000000101010000000001032600000001D30A000000000101000000000000', true],
        ];
    }

    #[DataProvider('seasonTwoGates')]
    public function test_evaluates_time_event_gates(string $expression, bool $expected): void
    {
        $this->assertSame($expected, WorldStateExpression::evaluate($expression, fn (int $timeEvent): bool => $timeEvent < 2771));
    }

    public function test_disabled_expression_always_passes(): void
    {
        $this->assertTrue(WorldStateExpression::evaluate('00', fn (): bool => false));
    }

    public function test_passes_time_event_id_to_callback(): void
    {
        $seen = [];

        WorldStateExpression::evaluate('01032600000001F5050000000001010100000000010326000000011006000000000101000000000000', function (int $timeEvent) use (&$seen): bool {
            $seen[] = $timeEvent;

            return true;
        });

        $this->assertSame([1525, 1552], $seen);
    }
}
