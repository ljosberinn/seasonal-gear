<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ManagesWagoExports;
use App\Gear\Db2Directory;
use App\Gear\ForeverResolver;
use App\Gear\WagoClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('forever:import
    {--build= : Game build to import; defaults to the latest WoW Forever build on wago.tools}
    {--from= : Directory with previously downloaded exports; skips downloading}
    {--keep : Keep the downloaded exports and print their location}
    {--force : Write the dataset even when it already has the build and its current hotfixes}
    {--output= : Where to write the dataset; defaults to config(forever.data_path)}')]
#[Description('Download DB2 tables from wago.tools and write the WoW Forever item dataset')]
class ImportForever extends Command
{
    use ManagesWagoExports;

    public function handle(WagoClient $wago): int
    {
        $build = $this->option('build') ?: $wago->latestBuild((string) config('forever.product'));
        $directory = $this->option('from') ?: storage_path('app/private/db2/'.config('forever.product').'-'.$build);
        $output = $this->option('output') ?: config('forever.data_path');

        if (! $this->option('from')) {
            $this->components->task("Downloading DB2 exports for {$build}", fn () => $wago->download($build, $directory, WagoClient::FOREVER_TABLES, WagoClient::FOREVER_GAME_TABLES));

            if (! $this->option('force') && $this->isUpToDate($output, $build, (new Db2Directory($directory))->hotfixes())) {
                $this->components->info("Dataset already has build {$build} and its current hotfixes.");
                $this->discardExports($directory);

                return self::SUCCESS;
            }
        }

        $dataset = null;

        $this->components->task('Resolving items', function () use ($build, $directory, &$dataset): void {
            $dataset = (new ForeverResolver(new Db2Directory($directory), (int) config('forever.max_level')))->resolve($build);
        });

        $this->line(count($dataset['items']).' items');

        // New Forever stat IDs only show up here; they need decoding against Wowhead before ForeverStat lists them.
        if ($dataset['unknownStats'] !== []) {
            $this->components->warn('Skipped unknown stat IDs (ID => stat count): '.json_encode($dataset['unknownStats']));
        }

        unset($dataset['unknownStats']);

        if ($dataset['items'] === []) {
            $this->components->error('No items resolved.');

            return self::FAILURE;
        }

        $this->writeDataset($output, $dataset);

        if (! $this->option('from')) {
            $this->discardExports($directory);
        }

        return self::SUCCESS;
    }
}
