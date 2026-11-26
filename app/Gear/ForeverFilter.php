<?php

namespace App\Gear;

readonly class ForeverFilter
{
    /**
     * @param  string|null  $class  key of config('forever.classes')
     * @param  list<string>  $stats  ForeverStat keys the item must all have
     */
    public function __construct(
        public ?string $class = null,
        public ?Slot $slot = null,
        public ?int $minLevel = null,
        public ?int $maxLevel = null,
        public array $stats = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->class === null && $this->slot === null && $this->minLevel === null && $this->maxLevel === null && $this->stats === [];
    }
}
