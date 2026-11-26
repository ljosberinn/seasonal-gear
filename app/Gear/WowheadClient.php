<?php

namespace App\Gear;

use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WowheadClient
{
    /**
     * Icon keys as wow.zamimg.com serves them. Achievements Wowhead doesn't answer for are left out,
     * since a missing icon shouldn't block a data refresh.
     *
     * @param  list<int>  $achievementIds
     * @return array<int, string> by achievement ID
     */
    public function achievementIcons(array $achievementIds): array
    {
        $responses = Http::pool(fn (Pool $pool): array => array_map(
            fn (int $id): mixed => $pool->as((string) $id)
                ->baseUrl((string) config('gear.wowhead.base_url'))
                ->withUserAgent((string) config('gear.user_agent'))
                ->connectTimeout(10)
                ->timeout(30)
                ->get("/tooltip/achievement/{$id}"),
            array_values(array_unique($achievementIds)),
        ));

        $icons = [];

        foreach ($responses as $id => $response) {
            $icon = $response instanceof Response && $response->successful() ? $response->json('icon') : null;

            if (is_string($icon) && $icon !== '') {
                $icons[(int) $id] = strtolower($icon);
            }
        }

        return $icons;
    }
}
