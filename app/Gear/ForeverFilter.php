<?php

namespace App\Gear;

readonly class ForeverFilter
{
    /**
     * @param  string|null  $class  key of config('forever.classes')
     * @param  list<string>  $stats  ForeverStat keys the item must all have
     * @param  string|null  $effect  effect category key, see {@see ForeverEffectClassifier::key()}
     * @param  float|null  $minEffectValue  lowest value of that effect, for categories with a unit
     */
    public function __construct(
        public ?string $class = null,
        public ?Slot $slot = null,
        public ?int $minLevel = null,
        public ?int $maxLevel = null,
        public ?int $minItemLevel = null,
        public ?int $maxItemLevel = null,
        public array $stats = [],
        public ?string $effect = null,
        public ?float $minEffectValue = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->class === null && $this->slot === null && $this->stats === [] && $this->effect === null
            && $this->minLevel === null && $this->maxLevel === null && $this->minItemLevel === null && $this->maxItemLevel === null;
    }
}
