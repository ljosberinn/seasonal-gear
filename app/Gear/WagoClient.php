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
        'Achievement',
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

    /**
     * Sent only for tables that have hotfixes, with the time of the newest one.
     */
    private const string HOTFIX_HEADER = 'X-Hotfix-Modified-At';

    public const array FOREVER_TABLES = ['Item', 'ItemSparse', 'RandPropPoints'];

    /**
     * Forever's ratings convert by character level rather than item level.
     */
    public const array FOREVER_GAME_TABLES = [
        'combat_ratings_by_level' => 1391669,
    ];

    public function latestLiveBuild(): string
    {
        return $this->latestBuild('wow');
    }

    /**
     * @param  string  $product  wago.tools product key, e.g. `wow` or `wowf`
     */
    public function latestBuild(string $product): string
    {
        $version = $this->request()->get('/api/builds/latest')->throw()->json("{$product}.version");

        if (! is_string($version) || $version === '') {
            throw new RuntimeException("wago.tools did not return a {$product} build version.");
        }

        return $version;
    }

    /**
     * Downloads the tables with hotfixes applied, since hotfixes can change item stats, and records
     * each table's newest hotfix for {@see Db2Directory::hotfixes()}.
     *
     * @param  list<string>  $tables
     * @param  array<string, int>  $gameTables  file name => CASC file data ID
     */
    public function download(string $build, string $directory, array $tables = self::TABLES, array $gameTables = self::GAME_TABLES): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException("Cannot create {$directory}");
        }

        $responses = Http::pool(function (Pool $pool) use ($build, $directory, $tables, $gameTables): array {
            $requests = [];

            foreach ($tables as $table) {
                $requests[] = $this->configure($pool->as($table))
                    ->sink(Db2Directory::tablePath($directory, $table))
                    ->get("/db2/{$table}/csv", ['build' => $build, 'useHotfixes' => 'true']);
            }

            foreach ($gameTables as $name => $fileDataId) {
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

        file_put_contents(
            Db2Directory::hotfixesPath($directory),
            json_encode($this->hotfixTimes(array_intersect_key($responses, array_flip($tables))), JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @param  array<string, Response>  $responses  by table
     * @return array<string, string>
     */
    private function hotfixTimes(array $responses): array
    {
        $times = array_filter(array_map(fn (Response $response): string => $response->header(self::HOTFIX_HEADER), $responses));
        ksort($times);

        return $times;
    }

    private function request(): PendingRequest
    {
        return $this->configure(Http::createPendingRequest());
    }

    private function configure(PendingRequest $request): PendingRequest
    {
        return $request->baseUrl((string) config('gear.wago.base_url'))
            ->withUserAgent((string) config('gear.user_agent'))
            ->connectTimeout(10)
            ->timeout(300)
            ->retry([1000, 5000, 15000], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException
                    && ($exception->response->serverError() || $exception->response->status() === 429)));
    }
}
