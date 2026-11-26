<?php

namespace Tests\Concerns;

use App\Gear\Db2Directory;

/**
 * Writes the DB2 exports `forever:import` reads into a temporary directory, removed by `removeForeverExports()`.
 */
trait WritesForeverExports
{
    private string $directory;

    protected function createForeverExportsDirectory(): void
    {
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'forever-'.bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function removeForeverExports(): void
    {
        array_map(unlink(...), glob($this->directory.DIRECTORY_SEPARATOR.'*'));
        rmdir($this->directory);
    }

    /**
     * @param  list<array{int, string, int, int, int, int, int, int, int, list<array{int, int}>}>  $items  ID, name, class, subclass, quality, item level, required level, inventory type, allowable class, [stat type, allocation] pairs
     */
    protected function writeExports(array $items): void
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

        file_put_contents(Db2Directory::gameTablePath($this->directory, 'combat_ratings_by_level'), implode("\n", [
            "Level\tDefense Skill\tDodge\tParry\tBlock\tHit - Melee\tCrit - Melee\tHaste - Melee\tExpertise",
            "59\t1\t12\t15\t5\t10\t14\t10\t10",
            "60\t1\t12\t15\t5\t10\t14\t10\t10",
        ]));
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
