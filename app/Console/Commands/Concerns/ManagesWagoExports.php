<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\File;

trait ManagesWagoExports
{
    /**
     * Whether the dataset it would overwrite already has the build and every hotfix in the
     * downloaded exports. Prints the tables whose hotfixes changed otherwise.
     *
     * @param  array<string, string>  $hotfixes  table => time of its newest hotfix
     */
    private function isUpToDate(string $output, string $build, array $hotfixes): bool
    {
        $previous = File::exists($output) ? File::json($output) : null;

        if (! is_array($previous) || ($previous['build'] ?? null) !== $build) {
            return false;
        }

        $recorded = $previous['hotfixes'] ?? [];
        $changed = array_keys(array_diff_assoc($hotfixes, $recorded) + array_diff_assoc($recorded, $hotfixes));

        if ($changed === []) {
            return true;
        }

        sort($changed);
        $this->table(
            ['Table', 'Recorded hotfix', 'Current hotfix'],
            array_map(fn (string $table): array => [$table, $recorded[$table] ?? '-', $hotfixes[$table] ?? '-'], $changed),
        );

        return false;
    }

    private function discardExports(string $directory): void
    {
        $this->option('keep')
            ? $this->line("Exports kept in {$directory}")
            : File::deleteDirectory($directory);
    }
}
