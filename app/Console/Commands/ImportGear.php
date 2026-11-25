<?php

namespace App\Console\Commands;

use App\Gear\Db2Directory;
use App\Gear\SeasonResolver;
use App\Gear\WagoClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('gear:import
    {--build= : Game build to import; defaults to the latest live build on wago.tools}
    {--from= : Directory with previously downloaded exports; skips downloading}
    {--keep : Keep the downloaded exports and print their location}
    {--output= : Where to write the dataset; defaults to config(gear.data_path)}')]
#[Description('Download DB2 tables from wago.tools and write the current season item dataset')]
class ImportGear extends Command
{
    /**
     * Source types the dataset must contain; a missing one means the season config or DB2 drifted.
     */
    private const array REQUIRED_SOURCE_TYPES = ['raid', 'dungeon', 'special', 'tier', 'catalyst', 'delve', 'world', 'crafted'];

    public function handle(WagoClient $wago): int
    {
        $build = $this->option('build') ?: $wago->latestLiveBuild();
        $directory = $this->option('from') ?: storage_path('app/private/db2/'.$build);

        if (! $this->option('from')) {
            $this->components->task("Downloading DB2 exports for {$build}", fn () => $wago->download($build, $directory));
        }

        $dataset = null;

        $this->components->task('Resolving season items', function () use ($build, $directory, &$dataset): void {
            $resolver = new SeasonResolver(new Db2Directory($directory), config('gear.season'), config('gear.expansion'));
            $dataset = $resolver->resolve($build);
        });

        $counts = array_count_values(array_column($dataset['sources'], 'type'));
        ksort($counts);
        $this->table(['Source', 'Entries'], collect($counts)->map(fn (int $count, string $type): array => [$type, $count])->values());
        $this->line(count($dataset['items']).' items');

        $missing = array_diff(self::REQUIRED_SOURCE_TYPES, array_keys($counts));

        if ($missing !== []) {
            $this->components->error('No items for source types: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $output = $this->option('output') ?: config('gear.data_path');
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
