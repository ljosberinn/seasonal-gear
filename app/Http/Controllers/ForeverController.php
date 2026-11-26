<?php

namespace App\Http\Controllers;

use App\Gear\ForeverCatalog;
use App\Gear\ForeverFilter;
use App\Gear\ForeverStat;
use App\Gear\Slot;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ForeverController extends Controller
{
    private const int PER_PAGE = 100;

    public function __invoke(Request $request, ForeverCatalog $catalog): View
    {
        $maxLevel = (int) config('forever.max_level');

        $validated = $request->validate([
            'class' => ['nullable', Rule::in(array_keys(config('forever.classes')))],
            'slot' => ['nullable', Rule::enum(Slot::class)],
            'min_level' => ['nullable', 'integer', "between:1,{$maxLevel}"],
            'max_level' => ['nullable', 'integer', "between:1,{$maxLevel}"],
            'min_item_level' => ['nullable', 'integer', 'min:1'],
            'max_item_level' => ['nullable', 'integer', 'min:1'],
            'stats' => ['array'],
            'stats.*' => [Rule::in(ForeverStat::keys())],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        [$minLevel, $upToLevel] = $this->range($validated, 'level');
        [$minItemLevel, $upToItemLevel] = $this->range($validated, 'item_level');

        $filter = new ForeverFilter(
            class: $validated['class'] ?? null,
            slot: isset($validated['slot']) ? Slot::from($validated['slot']) : null,
            minLevel: $minLevel,
            maxLevel: $upToLevel,
            minItemLevel: $minItemLevel,
            maxItemLevel: $upToItemLevel,
            stats: array_values(array_unique($validated['stats'] ?? [])),
        );

        $items = $catalog->search($filter);
        $page = (int) ($validated['page'] ?? 1);
        $paginator = new LengthAwarePaginator(
            array_slice($items, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            count($items),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('forever.index', [
            'build' => $catalog->build(),
            'ratingsPerPercent' => $catalog->ratingsPerPercent(),
            'paginator' => $paginator,
            'slots' => collect($paginator->items())->groupBy('slot'),
            'counts' => $catalog->counts($filter),
            'filter' => $filter,
            'classes' => array_intersect_key(config('classes'), config('forever.classes')),
            'maxLevel' => $maxLevel,
        ]);
    }

    /**
     * Bounds typed the wrong way round still mean a range.
     *
     * @param  array<string, mixed>  $validated
     * @return array{?int, ?int}
     */
    private function range(array $validated, string $name): array
    {
        $low = isset($validated["min_{$name}"]) ? (int) $validated["min_{$name}"] : null;
        $high = isset($validated["max_{$name}"]) ? (int) $validated["max_{$name}"] : null;

        return $low !== null && $high !== null && $low > $high ? [$high, $low] : [$low, $high];
    }
}
