<?php

namespace App\Gear;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class WagoClient
{
    public const array TABLES = [
        'ChallengeModeItemBonusOverride',
        'CraftingData',
        'Item',
        'ItemBonus',
        'ItemBonusListGroup',
        'ItemBonusListGroupEntry',
        'ItemBonusTree',
        'ItemBonusTreeNode',
        'ItemEffect',
        'ItemLimitCategory',
        'ItemScalingConfig',
        'ItemSet',
        'ItemSparse',
        'ItemSpecOverride',
        'ItemXBonusTree',
        'ItemXItemEffect',
        'JournalEncounter',
        'JournalEncounterItem',
        'JournalInstance',
        'JournalItemXDifficulty',
        'JournalTier',
        'JournalTierXInstance',
        'Map',
        'ModifierTree',
        'PlayerCondition',
        'RandPropPoints',
        'SharedString',
        'Spell',
        'WorldStateExpression',
    ];

    /**
     * CASC file data IDs of the game tables the stat formula needs.
     */
    public const array GAME_TABLES = [
        'stamina' => 1980632,
        'combat_ratings' => 1391670,
    ];

    public function latestLiveBuild(): string
    {
        $version = $this->request()->get('/api/builds/latest')->throw()->json('wow.version');

        if (! is_string($version) || $version === '') {
            throw new RuntimeException('wago.tools did not return a live build version.');
        }

        return $version;
    }

    /**
     * Downloads every table with hotfixes applied, since hotfixes can change item stats.
     */
    public function download(string $build, string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException("Cannot create {$directory}");
        }

        $responses = Http::pool(function (Pool $pool) use ($build, $directory): array {
            $requests = [];

            foreach (self::TABLES as $table) {
                $requests[] = $this->configure($pool->as($table))
                    ->sink(Db2Directory::tablePath($directory, $table))
                    ->get("/db2/{$table}/csv", ['build' => $build, 'useHotfixes' => 'true']);
            }

            foreach (self::GAME_TABLES as $name => $fileDataId) {
                $requests[] = $this->configure($pool->as($name))
                    ->sink(Db2Directory::gameTablePath($directory, $name))
                    ->get("/api/casc/{$fileDataId}", ['version' => $build]);
            }

            return $requests;
        }, concurrency: (int) config('gear.wago.concurrency'));

        foreach ($responses as $name => $response) {
            if (! $response instanceof Response) {
                throw new RuntimeException("Downloading {$name} failed.", previous: $response instanceof Throwable ? $response : null);
            }

            $response->throw();
        }
    }

    private function request(): PendingRequest
    {
        return $this->configure(Http::createPendingRequest());
    }

    private function configure(PendingRequest $request): PendingRequest
    {
        return $request->baseUrl((string) config('gear.wago.base_url'))
            ->withUserAgent((string) config('gear.wago.user_agent'))
            ->connectTimeout(10)
            ->timeout(300)
            ->retry([1000, 5000, 15000], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException
                    && ($exception->response->serverError() || $exception->response->status() === 429)));
    }
}
