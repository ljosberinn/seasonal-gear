<?php

namespace Tests\Feature;

use App\Gear\Db2Directory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\WritesForeverExports;
use Tests\TestCase;

class ImportForeverTest extends TestCase
{
    use WritesForeverExports;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createForeverExportsDirectory();
    }

    protected function tearDown(): void
    {
        $this->removeForeverExports();

        parent::tearDown();
    }

    public function test_writes_the_dataset_and_warns_about_unknown_stats(): void
    {
        $this->writeExports([[275000, "Zarmok's Plated Signet", 4, 0, 3, 45, 40, 11, -1, [[48, 7857], [7, 6428], [31, 3571], [999, 5000]]]]);
        File::put(Db2Directory::hotfixesPath($this->directory), json_encode(['ItemSparse' => '2026-11-26 04:47:19']));
        $output = $this->directory.DIRECTORY_SEPARATOR.'forever.json';

        $this->artisan('forever:import', ['--from' => $this->directory, '--build' => '1.60.1.71139', '--output' => $output])
            ->expectsOutputToContain('Skipped unknown stat IDs (ID => stat count): {"999":1}')
            ->assertSuccessful();

        $dataset = File::json($output);

        $this->assertSame('1.60.1.71139', $dataset['build']);
        $this->assertSame(['ItemSparse' => '2026-11-26 04:47:19'], $dataset['hotfixes']);
        $this->assertSame(['sta' => 9, 'block-value' => 11, 'hit' => 5], $dataset['items'][0]['stats']);
        $this->assertArrayNotHasKey('unknownStats', $dataset);
    }

    public function test_fails_without_writing_when_no_item_resolves(): void
    {
        $this->writeExports([[900001, 'Plain Ring', 4, 0, 3, 45, 40, 11, -1, []]]);
        $output = $this->directory.DIRECTORY_SEPARATOR.'forever.json';

        $this->artisan('forever:import', ['--from' => $this->directory, '--build' => '1.60.1.71139', '--output' => $output])
            ->expectsOutputToContain('No items resolved.')
            ->assertFailed();

        $this->assertFileDoesNotExist($output);
    }

    public function test_keeps_the_dataset_while_build_and_hotfixes_are_unchanged(): void
    {
        $output = $this->directory.DIRECTORY_SEPARATOR.'forever.json';
        $previous = json_encode(['build' => '1.60.1.71139', 'hotfixes' => ['ItemSparse' => '2026-11-26 04:47:19']]);
        File::put($output, $previous);
        $this->fakeHotfixes('2026-11-26 04:47:19');
        $this->app->useStoragePath($this->directory);

        $this->artisan('forever:import', ['--build' => '1.60.1.71139', '--output' => $output])
            ->expectsOutputToContain('Dataset already has build 1.60.1.71139 and its current hotfixes.')
            ->assertSuccessful();

        $this->assertSame($previous, File::get($output));
        $this->assertDirectoryDoesNotExist(storage_path('app/private/db2/wowf-1.60.1.71139'));
    }

    public function test_lists_tables_with_new_hotfixes_before_importing(): void
    {
        $output = $this->directory.DIRECTORY_SEPARATOR.'forever.json';
        File::put($output, json_encode(['build' => '1.60.1.71139', 'hotfixes' => ['ItemSparse' => '2026-11-18 08:40:18']]));
        $this->fakeHotfixes('2026-11-26 04:47:19');
        $this->app->useStoragePath($this->directory);

        $this->artisan('forever:import', ['--build' => '1.60.1.71139', '--output' => $output])
            ->expectsTable(['Table', 'Recorded hotfix', 'Current hotfix'], [['ItemSparse', '2026-11-18 08:40:18', '2026-11-26 04:47:19']])
            ->expectsOutputToContain('No items resolved.')
            ->assertFailed();

    }

    /**
     * Serves exports without items, so an import that gets past the hotfix check resolves nothing.
     */
    private function fakeHotfixes(string $itemSparseHotfix): void
    {
        $this->writeExports([]);

        Http::fake(function (Request $request) use ($itemSparseHotfix) {
            if (str_contains($request->url(), '/api/casc/')) {
                return Http::response(File::get(Db2Directory::gameTablePath($this->directory, 'combat_ratings_by_level')));
            }

            preg_match('#/db2/(\w+)/csv#', $request->url(), $match);

            return Http::response(
                File::get(Db2Directory::tablePath($this->directory, $match[1])),
                headers: $match[1] === 'ItemSparse' ? ['X-Hotfix-Modified-At' => $itemSparseHotfix] : [],
            );
        });
    }
}
