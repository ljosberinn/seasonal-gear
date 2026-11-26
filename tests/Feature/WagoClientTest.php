<?php

namespace Tests\Feature;

use App\Gear\Db2Directory;
use App\Gear\WagoClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WagoClientTest extends TestCase
{
    public function test_identifies_the_repository_in_requests(): void
    {
        Http::fake(['*/api/builds/latest' => Http::response(['wow' => ['version' => '12.1.5.70961']])]);

        $this->assertSame('12.1.5.70961', $this->app->make(WagoClient::class)->latestLiveBuild());

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('User-Agent', 'seasonal-gear (+https://github.com/ljosberinn/seasonal-gear)'));
    }

    public function test_reads_the_latest_build_of_the_requested_product(): void
    {
        Http::fake(['*/api/builds/latest' => Http::response(['wow' => ['version' => '12.1.5.70961'], 'wowf' => ['version' => '1.60.1.71139']])]);

        $this->assertSame('1.60.1.71139', $this->app->make(WagoClient::class)->latestBuild('wowf'));
    }

    public function test_records_the_hotfix_times_of_downloaded_tables(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wago-'.Str::random(8);

        Http::fake([
            '*/db2/ItemSparse/csv*' => Http::response('ID', headers: ['X-Hotfix-Modified-At' => '2026-11-18 08:40:18']),
            '*/db2/Item/csv*' => Http::response('ID'),
            '*/api/casc/*' => Http::response('Level'),
        ]);

        try {
            $this->app->make(WagoClient::class)->download('1.60.1.71139', $directory, ['ItemSparse', 'Item'], ['combat_ratings_by_level' => 1391669]);

            $this->assertSame(['ItemSparse' => '2026-11-18 08:40:18'], (new Db2Directory($directory))->hotfixes());
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
