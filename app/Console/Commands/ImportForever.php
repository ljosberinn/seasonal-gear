<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ComparesHotfixes;
use App\Gear\Db2Directory;
use App\Gear\ForeverResolver;
use App\Gear\WagoClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('forever:import
    {--build= : Game build to import; defaults to the latest WoW Forever build on wago.tools}
    {--from= : Directory with previously downloaded exports; skips downloading}
    {--keep : Keep the downloaded exports and print their location}
    {--force : Import even when the dataset already has the build and its current hotfixes}
    {--output= : Where to write the dataset; defaults to config(forever.data_path)}')]
#[Description('Download DB2 tables from wago.tools and write the WoW Forever item dataset')]
class ImportForever extends Command
{
    use ComparesHotfixes;

    public function handle(WagoClient $wago): int
    {
        $build = $this->option('build') ?: $wago->latestBuild((string) config('forever.product'));
        $directory = $this->option('from') ?: storage_path('app/private/db2/'.config('forever.product').'-'.$build);
        $output = $this->option('output') ?: config('forever.data_path');

        if (! $this->option('from') && ! $this->option('force') && $this->isUpToDate($wago, $output, $build, WagoClient::FOREVER_TABLES)) {
            $this->components->info("Dataset already has build {$build} and its current hotfixes.");

            return self::SUCCESS;
        }

        if (! $this->option('from')) {
            $this->components->task("Downloading DB2 exports for {$build}", fn () => $wago->download($build, $directory, WagoClient::FOREVER_TABLES, WagoClient::FOREVER_GAME_TABLES));
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

        File::ensureDirectoryExists(dirname($output));
        File::put($output, json_encode($dataset, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
        $this->components->info("Wrote {$output}");

        if (! $this->option('from')) {
            $this->option('keep')
                ? $this->line("Exports kept in {$directory}")
                : File::deleteDirectory($directory);
        }

        return self::SUCCESS;
    }
}
