<?php

namespace App\Http\Controllers;

use App\Gear\GearCatalog;
use App\Gear\GearFilter;
use App\Gear\PagePreview;
use App\Gear\Slot;
use App\Gear\Stat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GearController extends Controller
{
    public function __invoke(Request $request, GearCatalog $catalog, ?string $class = null, ?string $spec = null): View
    {
        $classes = config('classes');
        $selectedClass = $class === null ? null : ($classes[$class] ?? abort(404));

        if ($spec !== null && ! isset($selectedClass['specs'][$spec])) {
            abort(404);
        }

        $validated = $request->validate([
            'slot' => ['nullable', Rule::enum(Slot::class)],
            'stats' => ['array'],
            'stats.*' => [Rule::in(Stat::SECONDARY_KEYS)],
            'sources' => ['array'],
            'sources.*' => [Rule::in(array_keys(GearCatalog::SOURCE_TYPES))],
            'track' => ['nullable', Rule::in(array_keys($catalog->tracks()))],
        ]);

        $filter = new GearFilter(
            slot: isset($validated['slot']) ? Slot::from($validated['slot']) : null,
            stats: array_values(array_unique($validated['stats'] ?? [])),
            sources: $request->has('sources') ? array_values(array_unique($validated['sources'])) : GearCatalog::DEFAULT_SOURCE_TYPES,
            class: $selectedClass,
            specs: $spec === null ? [] : [$spec],
            track: $validated['track'] ?? null,
        );

        $items = $catalog->search($filter);
        $meta = $catalog->meta();

        return view('gear.index', [
            'meta' => $meta,
            'preview' => PagePreview::gear($filter, count($items), $selectedClass, $spec, $meta['season']['name']),
            'items' => $items,
            'counts' => $catalog->counts($filter),
            'tracks' => $catalog->tracks(),
            'filter' => $filter,
            'classes' => $classes,
            'classKey' => $class,
            'specKey' => $spec,
        ]);
    }
}
