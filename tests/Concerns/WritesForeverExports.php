<?php

namespace Tests\Concerns;

use App\Gear\Db2Directory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Writes the DB2 exports `forever:import` reads into a temporary directory, removed by `removeForeverExports()`.
 */
trait WritesForeverExports
{
    private string $directory;

    protected function createForeverExportsDirectory(): void
    {
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'forever-'.Str::random(8);
        File::makeDirectory($this->directory);
    }

    protected function removeForeverExports(): void
    {
        File::deleteDirectory($this->directory);
    }

    /**
     * @param  list<array{int, string, int, int, int, int, int, int, int, list<array{int, int}>}>  $items  ID, name, class, subclass, quality, item level, required level, inventory type, allowable class, [stat type, allocation] pairs
     * @param  list<array{int, int, int, int}>  $effects  item ID, trigger type, spell ID, cooldown in ms
     * @param  list<array{id: int, description?: string, effects?: list<array{0: float, 1?: float, 2?: int, effect?: int, aura?: int, triggerSpell?: int, targets?: array{int, int}}>, duration?: int, procChance?: int, procCooldown?: int, procFlags?: int}>  $spells  effects are [base points, variance, period in ms] by index, applying an aura (`effect` 6) with no `aura` unless given; duration in ms
     */
    protected function writeExports(array $items, array $effects = [], array $spells = []): void
    {
        $sparseHeader = ['ID', 'Display_lang', 'OverallQualityID', 'ItemLevel', 'RequiredLevel', 'InventoryType', 'AllowableClass'];

        for ($index = 0; $index < 10; $index++) {
            $sparseHeader[] = "StatModifier_bonusStat_{$index}";
            $sparseHeader[] = "StatPercentEditor_{$index}";
        }

        $itemRows = [['ID', 'ClassID', 'SubclassID']];
        $sparseRows = [$sparseHeader];

        foreach ($items as [$id, $name, $classId, $subclassId, $quality, $itemLevel, $requiredLevel, $inventoryType, $allowableClass, $stats]) {
            $itemRows[] = [$id, $classId, $subclassId];
            $statPairs = array_pad($stats, 10, [-1, 0]);
            $sparseRows[] = [$id, $name, $quality, $itemLevel, $requiredLevel, $inventoryType, $allowableClass, ...array_merge(...$statPairs)];
        }

        $budgetHeader = ['ID'];

        foreach (['Epic', 'Superior', 'Good'] as $tier) {
            for ($group = 0; $group < 5; $group++) {
                $budgetHeader[] = "{$tier}_{$group}";
            }
        }

        $this->writeCsv('Item', $itemRows);
        $this->writeCsv('ItemSparse', $sparseRows);
        $this->writeCsv('RandPropPoints', [
            $budgetHeader,
            [45, 34, 25, 19, 14, 11, 26, 19, 14, 11, 8, 19, 14, 10, 8, 6],
            [47, 35, 26, 20, 15, 11, 27, 20, 15, 11, 8, 20, 15, 11, 8, 6],
            [75, 52, 40, 30, 22, 16, 40, 31, 23, 17, 13, 30, 23, 17, 13, 10],
            [88, 58, 45, 33, 25, 19, 45, 35, 25, 19, 14, 34, 26, 19, 14, 11],
        ]);
        $this->writeEffects($effects);
        $this->writeSpells($spells);

        File::put(Db2Directory::gameTablePath($this->directory, 'combat_ratings_by_level'), implode("\n", [
            "Level\tDefense Skill\tDodge\tParry\tBlock\tHit - Melee\tCrit - Melee\tHaste - Melee\tExpertise",
            "59\t1\t12\t15\t5\t10\t14\t10\t10",
            "60\t1\t12\t15\t5\t10\t14\t10\t10",
        ]));
    }

    /**
     * @param  list<array{int, int, int, int}>  $effects
     */
    private function writeEffects(array $effects): void
    {
        $effectRows = [['ID', 'LegacySlotIndex', 'TriggerType', 'CoolDownMSec', 'CategoryCoolDownMSec', 'SpellID']];
        $linkRows = [['ID', 'ItemEffectID', 'ItemID']];

        foreach ($effects as $index => [$itemId, $triggerType, $spellId, $cooldown]) {
            $effectRows[] = [$index + 1, $index, $triggerType, $cooldown, -1, $spellId];
            $linkRows[] = [$index + 1, $index + 1, $itemId];
        }

        $this->writeCsv('ItemEffect', $effectRows);
        $this->writeCsv('ItemXItemEffect', $linkRows);
    }

    /**
     * @param  list<array{id: int, description?: string, effects?: list<array{0: float, 1?: float, 2?: int, effect?: int, aura?: int, triggerSpell?: int, targets?: array{int, int}}>, duration?: int, procChance?: int, procCooldown?: int, procFlags?: int}>  $spells
     */
    private function writeSpells(array $spells): void
    {
        $spellRows = [['ID', 'Description_lang']];
        $effectRows = [['ID', 'DifficultyID', 'EffectIndex', 'Effect', 'EffectAura', 'EffectTriggerSpell', 'EffectAuraPeriod', 'EffectChainAmplitude', 'EffectChainTargets', 'EffectPointsPerResource', 'EffectAmplitude', 'Variance', 'EffectBasePointsF', 'EffectRadiusIndex_0', 'EffectRadiusIndex_1', 'ImplicitTarget_0', 'ImplicitTarget_1', 'SpellID']];
        $miscRows = [['ID', 'DifficultyID', 'DurationIndex', 'RangeIndex', 'SpellID']];
        $auraRows = [['ID', 'DifficultyID', 'CumulativeAura', 'ProcCategoryRecovery', 'ProcChance', 'ProcCharges', 'ProcTypeMask_0', 'SpellID']];
        $durationRows = [['ID', 'Duration']];

        foreach ($spells as $spell) {
            $spellRows[] = [$spell['id'], $spell['description'] ?? ''];

            foreach ($spell['effects'] ?? [] as $index => $effect) {
                $effectRows[] = [
                    count($effectRows), 0, $index, $effect['effect'] ?? 6, $effect['aura'] ?? 0, $effect['triggerSpell'] ?? 0, $effect[2] ?? 0, 1, 0, 0, 0,
                    $effect[1] ?? 0, $effect[0], 0, 0, ...$effect['targets'] ?? [1, 0], $spell['id'],
                ];
            }

            if (isset($spell['duration'])) {
                $durationRows[] = [count($durationRows), $spell['duration']];
            }

            $miscRows[] = [count($miscRows), 0, isset($spell['duration']) ? count($durationRows) - 1 : 0, 0, $spell['id']];

            if (isset($spell['procChance']) || isset($spell['procCooldown']) || isset($spell['procFlags'])) {
                $auraRows[] = [count($auraRows), 0, 0, $spell['procCooldown'] ?? 0, $spell['procChance'] ?? 0, 0, $spell['procFlags'] ?? 0, $spell['id']];
            }
        }

        $this->writeCsv('Spell', $spellRows);
        $this->writeCsv('SpellEffect', $effectRows);
        $this->writeCsv('SpellMisc', $miscRows);
        $this->writeCsv('SpellAuraOptions', $auraRows);
        $this->writeCsv('SpellDuration', $durationRows);
        $this->writeCsv('SpellRadius', [['ID', 'Radius']]);
        $this->writeCsv('SpellRange', [['ID', 'RangeMax_0']]);
        $this->writeCsv('SpellTargetRestrictions', [['ID', 'DifficultyID', 'MaxTargets', 'SpellID']]);
        $this->writeCsv('SpellDescriptionVariables', [['ID', 'Variables']]);
        $this->writeCsv('SpellXDescriptionVariables', [['ID', 'SpellID', 'SpellDescriptionVariablesID']]);
    }

    /**
     * @param  list<list<int|string>>  $rows
     */
    private function writeCsv(string $table, array $rows): void
    {
        $handle = fopen(Db2Directory::tablePath($this->directory, $table), 'w');

        foreach ($rows as $row) {
            fputcsv($handle, $row, escape: '');
        }

        fclose($handle);
    }
}
