<?php

namespace App\Console\Commands\Concerns;

use App\Gear\WagoClient;
use Illuminate\Support\Facades\File;

/**
 * Lets an import skip downloading when the dataset it would overwrite already has the build and
 * every hotfix wago.tools serves for it.
 */
trait ComparesHotfixes
{
    /**
     * Prints the tables whose hotfixes changed since the dataset was written.
     *
     * @param  list<string>  $tables
     */
    private function isUpToDate(WagoClient $wago, string $output, string $build, array $tables): bool
    {
        $previous = File::exists($output) ? json_decode(File::get($output), true) : null;

        if (! is_array($previous) || ($previous['build'] ?? null) !== $build) {
            return false;
        }

        $recorded = $previous['hotfixes'] ?? [];
        $current = $wago->hotfixes($build, $tables);
        $changed = array_keys(array_diff_assoc($current, $recorded) + array_diff_assoc($recorded, $current));

        if ($changed === []) {
            return true;
        }

        sort($changed);
        $this->table(
            ['Table', 'Recorded hotfix', 'Current hotfix'],
            array_map(fn (string $table): array => [$table, $recorded[$table] ?? '-', $current[$table] ?? '-'], $changed),
        );

        return false;
    }
}
