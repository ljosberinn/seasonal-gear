<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\File;

/**
 * Points the catalog at a small fixture dataset, deleted again after the test.
 */
trait UsesGearDataset
{
    protected function useGearDataset(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'gear');
        File::put($path, json_encode($this->gearDataset()));
        config(['gear.data_path' => $path]);
        $this->beforeApplicationDestroyed(fn () => File::delete($path));
    }

    /**
     * @return array<string, mixed>
     */
    private function gearDataset(): array
    {
        $hero = ['bonusList' => 12846, 'itemLevel' => 321];
        $myth = ['bonusList' => 12854, 'itemLevel' => 334];
        $heroOnly = ['itemLevel' => 321, 'tracks' => ['hero' => $hero]];
        $item = fn (int $id, string $name, string $slot, int $inventoryType, int $classId, int $subclassId, array $primaries, array $secondaries, array $extra = []): array => [
            'id' => $id, 'name' => $name, 'slot' => $slot, 'inventoryType' => $inventoryType, 'classId' => $classId,
            'subclassId' => $subclassId, 'quality' => 4, 'allowableClass' => -1, 'specs' => [], 'primaries' => $primaries,
            'secondaries' => $secondaries, 'choiceStats' => false, 'itemLevel' => 334, 'tracks' => ['hero' => $hero, 'myth' => $myth],
            'stats' => ['sta' => 1000] + $secondaries, 'sockets' => 0, 'onUse' => false, 'onEquip' => false, 'embellished' => false, 'itemSet' => null,
            ...$extra,
        ];
        $instanceIds = ['The Venomous Abyss' => 1320, 'Voidscar Arena' => 1313, 'The Blinding Vale' => 1309];
        $source = fn (int $itemId, string $type, ?string $instance = null, ?string $encounter = null): array => [
            'itemId' => $itemId, 'type' => $type, 'instanceId' => $instanceIds[$instance] ?? null, 'instance' => $instance,
            'encounterId' => $encounter ? 1 : null, 'encounter' => $encounter, 'itemLevel' => 334,
        ];

        return [
            'build' => '12.1.5.70961',
            'season' => ['slug' => 'test', 'name' => 'Test Season'],
            'instances' => [
                1320 => ['name' => 'The Venomous Abyss', 'icon' => 'inv_121_achievement_raid_ulatek', 'iconFileId' => 8039569],
                1313 => ['name' => 'Voidscar Arena', 'icon' => 'inv_achievement_dungeon_voidscararena', 'iconFileId' => 7439626],
                1309 => ['name' => 'The Blinding Vale', 'icon' => null, 'iconFileId' => null],
            ],
            'tracks' => ['champion' => 'Champion', 'hero' => 'Hero', 'myth' => 'Myth'],
            'items' => [
                $item(268265, 'Aqirbane Reliquary', 'neck', 2, 4, 0, [], ['crit' => 3000, 'haste' => 3000], ['itemLevel' => 344, 'tracks' => ['hero' => $hero, 'myth' => ['bonusList' => 13848, 'itemLevel' => 344]]]),
                $item(268249, "Vile Alchemist's Band", 'finger', 11, 4, 0, [], ['mastery' => 14350, 'crit' => 3150]),
                $item(251229, 'Visor of the Predator', 'head', 1, 4, 4, ['str', 'int'], ['crit' => 4550, 'mastery' => 2450]),
                $item(251199, 'Worldroot Canopy', 'head', 1, 4, 1, ['int'], ['mastery' => 3500, 'crit' => 3500]),
                $item(900001, 'Hasty Helm', 'head', 1, 4, 4, ['str'], ['haste' => 4000, 'crit' => 3000], $heroOnly),
                $item(271499, "Calamitous Echo's Sundered Peaks", 'shoulder', 3, 4, 3, ['agi', 'int'], ['crit' => 4846, 'vers' => 2154], ['allowableClass' => 4096]),
                $item(239652, "Martyr's Crown", 'head', 1, 4, 1, ['int'], [], ['choiceStats' => true, 'embellished' => true, 'itemLevel' => null, 'tracks' => [], 'stats' => null]),
                $item(900002, 'Greatsword of Testing', 'two-hand', 17, 2, 8, ['str'], ['haste' => 3500, 'vers' => 3500]),
                $item(900003, 'Staff of Testing', 'two-hand', 17, 2, 10, ['int'], ['haste' => 3500, 'vers' => 3500]),
                $item(900006, 'Maul of Testing', 'two-hand', 17, 2, 5, ['int'], ['crit' => 3500, 'vers' => 3500]),
                $item(900004, 'Bulwark of Testing', 'off-hand', 14, 4, 6, ['str'], ['haste' => 3500, 'vers' => 3500], $heroOnly),
                $item(270175, "Voracious Heart of Ula'tek", 'trinket', 12, 4, 0, [], ['crit' => 6666], ['specs' => [71, 72, 73, 103, 577], 'sockets' => 1, 'onUse' => true]),
                $item(900005, 'Catalyzed Cowl', 'head', 1, 4, 4, ['str'], ['haste' => 3500, 'crit' => 3500]),
            ],
            'sources' => [
                $source(268249, 'raid', 'The Venomous Abyss', 'Vashnik the Malignant'),
                $source(268265, 'raid', 'The Venomous Abyss', "Ula'tek"),
                $source(270175, 'raid', 'The Venomous Abyss', "Ula'tek"),
                $source(251229, 'dungeon', 'Voidscar Arena'),
                $source(251199, 'dungeon', 'The Blinding Vale'),
                $source(900001, 'delve'),
                $source(271499, 'tier', 'The Venomous Abyss'),
                $source(239652, 'crafted'),
                $source(900002, 'world'),
                $source(900003, 'world'),
                $source(900006, 'world'),
                $source(900004, 'delve'),
                $source(900005, 'catalyst', 'The Venomous Abyss'),
            ],
        ];
    }
}
