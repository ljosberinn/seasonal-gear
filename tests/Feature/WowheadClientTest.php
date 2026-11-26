<?php

namespace Tests\Feature;

use App\Gear\WowheadClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WowheadClientTest extends TestCase
{
    public function test_keeps_the_icon_key_wowhead_serves_and_skips_failed_lookups(): void
    {
        Http::fake([
            '*/tooltip/achievement/63521' => Http::response(['name' => 'The Venomous Abyss', 'icon' => '8039569']),
            '*/tooltip/achievement/61508' => Http::response(['name' => 'Voidscar Arena', 'icon' => 'INV_Achievement_Dungeon_VoidscarArena']),
            '*/tooltip/achievement/1' => Http::response(status: 404),
        ]);

        $icons = $this->app->make(WowheadClient::class)->achievementIcons([63521, 61508, 1, 63521]);

        $this->assertSame([63521 => '8039569', 61508 => 'inv_achievement_dungeon_voidscararena'], $icons);
        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('User-Agent', 'seasonal-gear (+https://github.com/ljosberinn/seasonal-gear)'));
    }
}
