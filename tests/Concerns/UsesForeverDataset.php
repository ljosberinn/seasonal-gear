<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\File;

/**
 * Points the Forever catalog at a small fixture dataset, deleted again after the test.
 */
trait UsesForeverDataset
{
    /**
     * @param  list<array<string, mixed>>  $extraItems
     */
    protected function useForeverDataset(array $extraItems = []): void
    {
        $path = tempnam(sys_get_temp_dir(), 'forever');
        File::put($path, json_encode([
            'build' => '1.60.1.71139',
            'ratingsPerPercent' => ['dodge' => 12, 'parry' => 15, 'block' => 5, 'hit' => 10, 'crit' => 14, 'haste' => 10, 'expertise' => 10],
            'items' => [...$this->foreverItems(), ...$extraItems],
        ]));
        config(['forever.data_path' => $path]);
        $this->beforeApplicationDestroyed(fn () => File::delete($path));
    }

    /**
     * @param  array<string, int>  $stats
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function foreverItem(int $id, string $name, string $slot, int $inventoryType, int $classId, int $subclassId, int $itemLevel, int $requiredLevel, array $stats, array $extra = []): array
    {
        return [
            'id' => $id, 'name' => $name, 'quality' => 3, 'itemLevel' => $itemLevel, 'requiredLevel' => $requiredLevel,
            'inventoryType' => $inventoryType, 'slot' => $slot, 'classId' => $classId, 'subclassId' => $subclassId,
            'allowableClass' => -1, 'stats' => $stats, ...$extra,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function foreverItems(): array
    {
        return [
            $this->foreverItem(275000, "Zarmok's Plated Signet", 'finger', 11, 4, 0, 45, 40, ['sta' => 9, 'block-value' => 11, 'hit' => 5]),
            $this->foreverItem(19431, "Styleen's Impeding Scarab", 'trinket', 12, 4, 0, 75, 60, ['defense' => 13, 'block' => 25, 'block-value' => 24], ['quality' => 4]),
            $this->foreverItem(22417, 'Dreadnaught Legplates', 'legs', 7, 4, 4, 88, 60, ['str' => 23, 'sta' => 37, 'defense' => 13, 'dodge' => 12, 'block-value' => 32], ['quality' => 4]),
            $this->foreverItem(14141, 'Ghostweave Leggings', 'legs', 7, 4, 1, 55, 50, ['int' => 12, 'spell-power' => 9]),
            $this->foreverItem(18499, 'Barrier Shield', 'off-hand', 14, 4, 6, 62, 57, ['sta' => 10, 'block-value' => 23]),
            $this->foreverItem(11263, 'Nether Force Wand', 'ranged', 26, 2, 19, 40, 35, ['int' => 3, 'arcane-damage' => 9], ['allowableClass' => 128]),
            $this->foreverItem(240035, 'Inquisition Crown', 'head', 1, 4, 4, 98, 60, ['str' => 20, 'block-value' => 23], ['quality' => 4, 'allowableClass' => 2]),
            $this->foreverItem(7348, "Fletcher's Gloves", 'hands', 10, 4, 2, 25, 20, ['parry' => -15, 'crit' => 14], ['quality' => 2]),
            $this->foreverItem(1449, 'Minor Channeling Ring', 'finger', 11, 4, 0, 24, 0, ['int' => 2, 'spell-power' => 5]),
        ];
    }
}
