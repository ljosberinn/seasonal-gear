<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\ManagesWagoExports;
use App\Gear\Db2Directory;
use App\Gear\SeasonResolver;
use App\Gear\WagoClient;
use App\Gear\WowheadClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gear:import
    {--build= : Game build to import; defaults to the latest live build on wago.tools}
    {--from= : Directory with previously downloaded exports; skips downloading}
    {--keep : Keep the downloaded exports and print their location}
    {--force : Write the dataset even when it already has the build and its current hotfixes}
    {--output= : Where to write the dataset; defaults to config(gear.data_path)}')]
#[Description('Download DB2 tables from wago.tools and write the current season item dataset')]
class ImportGear extends Command
{
    use ManagesWagoExports;

    /**
     * Source types the dataset must contain; a missing one means the season config or DB2 drifted.
     */
    private const array REQUIRED_SOURCE_TYPES = ['raid', 'dungeon', 'tier', 'catalyst', 'delve', 'world', 'crafted'];

    public function handle(WagoClient $wago, WowheadClient $wowhead): int
    {
        $build = $this->option('build') ?: $wago->latestLiveBuild();
        $directory = $this->option('from') ?: storage_path('app/private/db2/'.$build);
        $output = $this->option('output') ?: config('gear.data_path');

        if (! $this->option('from')) {
            $this->components->task("Downloading DB2 exports for {$build}", fn () => $wago->download($build, $directory));

            if (! $this->option('force') && $this->isUpToDate($output, $build, (new Db2Directory($directory))->hotfixes())) {
                $this->components->info("Dataset already has build {$build} and its current hotfixes.");
                $this->discardExports($directory);

                return self::SUCCESS;
            }
        }

        $dataset = null;

        $this->components->task('Resolving season items', function () use ($build, $directory, &$dataset): void {
            $resolver = new SeasonResolver(new Db2Directory($directory), config('gear.season'), config('gear.expansion'));
            $dataset = $resolver->resolve($build);
        });

        $this->components->task('Resolving instance icons on Wowhead', function () use ($wowhead, &$dataset): void {
            $icons = $wowhead->achievementIcons(array_values(array_filter(array_column($dataset['instances'], 'achievementId'))));

            foreach ($dataset['instances'] as &$instance) {
                $instance['icon'] = $icons[$instance['achievementId']] ?? null;
            }
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

        $this->writeDataset($output, $dataset);

        if (! $this->option('from')) {
            $this->discardExports($directory);
        }

        return self::SUCCESS;
    }
}
