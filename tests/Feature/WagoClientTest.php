<?php

namespace Tests\Feature;

use App\Gear\WagoClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WagoClientTest extends TestCase
{
    public function test_identifies_the_repository_in_requests(): void
    {
        Http::fake(['*/api/builds/latest' => Http::response(['wow' => ['version' => '12.1.5.70961']])]);

        $this->assertSame('12.1.5.70961', $this->app->make(WagoClient::class)->latestLiveBuild());

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('User-Agent', 'seasonal-gear (+https://github.com/ljosberinn/seasonal-gear)'));
    }
}
