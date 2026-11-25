<?php

namespace App\Gear;

readonly class GearFilter
{
    /**
     * @param  list<string>  $stats  secondary stat keys the item must all have
     * @param  list<string>  $sources  source types to include; empty means all
     * @param  array{id: int, armor: int, specs: array<string, array{primary: string, weapons: list<int>, shield?: bool, off_hand?: bool}>}|null  $class
     * @param  list<string>  $specs  spec keys of `$class` to match; empty means any
     */
    public function __construct(
        public ?Slot $slot = null,
        public array $stats = [],
        public array $sources = [],
        public ?array $class = null,
        public array $specs = [],
    ) {}
}
