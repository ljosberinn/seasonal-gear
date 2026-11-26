<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
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
        $output = $this->directory.DIRECTORY_SEPARATOR.'forever.json';

        $this->artisan('forever:import', ['--from' => $this->directory, '--build' => '1.60.1.71139', '--output' => $output])
            ->expectsOutputToContain('Skipped unknown stat IDs (ID => stat count): {"999":1}')
            ->assertSuccessful();

        $dataset = json_decode(File::get($output), true);

        $this->assertSame('1.60.1.71139', $dataset['build']);
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
}
