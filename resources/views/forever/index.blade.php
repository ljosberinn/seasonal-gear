@php
    use App\Gear\ForeverStat;
    use App\Gear\GearCatalog;
    use App\Gear\Slot;

    // the rarer stat groups start collapsed unless one of their stats is picked
    $primaryGroups = ['attributes', 'defense', 'physical', 'spell'];
    $statsByGroup = collect(ForeverStat::cases())->groupBy(fn (ForeverStat $stat): string => $stat->group());
    $showsMoreStats = collect($filter->stats)->contains(fn (string $key): bool => ! in_array(ForeverStat::fromKey($key)->group(), $primaryGroups, true));
    $classOptions = [
        ['href' => request()->fullUrlWithQuery(['class' => null, 'page' => null]), 'label' => 'All Classes', 'active' => $filter->class === null, 'icon' => 'inv_misc_questionmark', 'color' => null, 'count' => $counts['anyClass']],
        ...collect($classes)->sortBy('name')->map(fn (array $class, string $key): array => [
            'href' => request()->fullUrlWithQuery(['class' => $key, 'page' => null]),
            'label' => $class['name'],
            'active' => $filter->class === $key,
            'icon' => $class['icon'],
            'color' => $class['color'],
            'count' => $counts['classes'][$key],
        ])->values(),
    ];
    $selectedClass = collect($classOptions)->firstWhere('active', true);
@endphp

<x-layout title="WoW Forever" :build="$build">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <h1 class="sr-only">WoW Forever Gear</h1>

        <nav aria-label="Class">
            <x-filter.nav-select class="sm:hidden" label="Class" :options="$classOptions" :icon="$selectedClass['icon']" :color="$selectedClass['color']" />

            <ul class="hidden grid-cols-5 gap-1.5 sm:grid lg:grid-cols-10">
                @foreach ($classOptions as $option)
                    <li><x-filter.card class="h-full" :href="$option['href']" :active="$option['active']" :icon="$option['icon']" :color="$option['color']" :count="$option['count']">{{ $option['label'] }}</x-filter.card></li>
                @endforeach
            </ul>
        </nav>

        <form method="get" action="{{ route('forever.index') }}" data-auto-submit class="z-10 mt-6 flex flex-col gap-3 rounded-md border border-gray-200 bg-white/90 p-3 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-gray-950/90">
            @if ($filter->class !== null)
                <input type="hidden" name="class" value="{{ $filter->class }}">
            @endif

            <div role="group" aria-label="Slot and Required Level" class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <x-filter.select name="slot" aria-label="Slot" :active="$filter->slot !== null">
                    <option value="">Any Slot ({{ $counts['anySlot'] }})</option>
                    @foreach (Slot::cases() as $slotOption)
                        <option value="{{ $slotOption->value }}" @selected($filter->slot === $slotOption)>{{ $slotOption->label() }} ({{ $counts['slots'][$slotOption->value] }})</option>
                    @endforeach
                </x-filter.select>

                <fieldset @class([
                    'flex flex-wrap items-center gap-2 text-sm',
                    'font-bold' => $filter->minLevel !== null || $filter->maxLevel !== null,
                ])>
                    <legend class="float-left mr-1 text-gray-600 dark:text-gray-400">Required Level</legend>
                    <label class="sr-only" for="min-level">Minimum Required Level</label>
                    <input id="min-level" type="range" name="min_level" min="1" max="{{ $maxLevel }}" value="{{ $filter->minLevel ?? 1 }}" data-output="min-level-value" class="w-32 accent-teal-600">
                    <output id="min-level-value" for="min-level" class="w-6 text-right tabular-nums">{{ $filter->minLevel ?? 1 }}</output>
                    <span aria-hidden="true">–</span>
                    <label class="sr-only" for="max-level">Maximum Required Level</label>
                    <output id="max-level-value" for="max-level" class="w-6 tabular-nums">{{ $filter->maxLevel ?? $maxLevel }}</output>
                    <input id="max-level" type="range" name="max_level" min="1" max="{{ $maxLevel }}" value="{{ $filter->maxLevel ?? $maxLevel }}" data-output="max-level-value" class="w-32 accent-teal-600">
                </fieldset>

                @if (! $filter->isEmpty())
                    <a href="{{ route('forever.index') }}" class="ml-auto text-sm text-gray-500 underline underline-offset-4 hover:text-primary-500 dark:text-gray-400 dark:hover:text-primary-400">Reset Filters</a>
                @endif

                <noscript>
                    <button type="submit" class="rounded-md bg-teal-600 px-3 py-0.5 text-sm font-bold text-white">Apply Filters</button>
                </noscript>
            </div>

            <div role="group" aria-label="Stats" class="flex flex-col gap-2">
                <div class="flex">
                    <x-filter.chip :href="request()->fullUrlWithoutQuery(['stats', 'page'])" :active="$filter->stats === []" :count="$counts['anyStat']">Any Stats</x-filter.chip>
                </div>

                @foreach ($primaryGroups as $group)
                    <x-forever.stat-row :label="ForeverStat::GROUPS[$group]" :stats="$statsByGroup[$group]" :picked="$filter->stats" :counts="$counts['stats']" />
                @endforeach

                <details @if ($showsMoreStats) open @endif>
                    <summary class="cursor-pointer text-sm text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100">More Stats</summary>
                    <div class="mt-2 flex flex-col gap-2">
                        @foreach (array_diff(array_keys(ForeverStat::GROUPS), $primaryGroups) as $group)
                            <x-forever.stat-row :label="ForeverStat::GROUPS[$group]" :stats="$statsByGroup[$group]" :picked="$filter->stats" :counts="$counts['stats']" />
                        @endforeach
                    </div>
                </details>
            </div>
        </form>

        <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">
            {{ $paginator->total() }} {{ Str::plural('item', $paginator->total()) }}
            @if ($paginator->hasPages())
                · showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}
            @endif
        </p>

        @forelse ($slots as $slot => $items)
            @php($showsType = collect($items)->contains(fn (array $item): bool => GearCatalog::typeLabel($item) !== ''))
            <section class="mt-6">
                <h2 class="text-lg font-semibold">{{ Slot::from($slot)->label() }}</h2>

                <div class="mt-2 overflow-x-auto rounded-md border border-gray-200 dark:border-gray-800">
                    <table class="min-w-full table-fixed divide-y divide-gray-200 text-sm dark:divide-gray-800">
                        <thead class="bg-gray-50 text-left text-xs tracking-wide text-gray-500 uppercase dark:bg-gray-900/40 dark:text-gray-400">
                            <tr>
                                <th class="w-80 px-3 py-2">Item</th>
                                @if ($showsType)
                                    <th class="w-28 px-3 py-2">Type</th>
                                @endif
                                <th class="w-24 px-3 py-2 text-right">Item Level</th>
                                <th class="w-24 px-3 py-2 text-right">Req. Level</th>
                                <th class="px-3 py-2">Stats</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 *:even:bg-gray-50 dark:*:even:bg-gray-900/40">
                            @foreach ($items as $item)
                                <tr>
                                    <td class="px-3 py-2">
                                        <a href="https://www.wowhead.com/forever/item={{ $item['id'] }}" class="group inline-flex items-center gap-2" target="_blank" rel="noopener">
                                            {{-- Wowhead's script prepends the item icon in here --}}
                                            <span @class([
                                                'font-medium group-hover:underline',
                                                'text-orange-600 dark:text-orange-400' => $item['quality'] === 5,
                                                'text-purple-700 dark:text-purple-400' => $item['quality'] === 4,
                                                'text-blue-700 dark:text-blue-400' => $item['quality'] === 3,
                                                'text-green-700 dark:text-green-400' => $item['quality'] === 2,
                                            ])>{{ $item['name'] }}</span>
                                        </a>
                                    </td>
                                    @if ($showsType)
                                        <td class="px-3 py-2 whitespace-nowrap">{{ GearCatalog::typeLabel($item) }}</td>
                                    @endif
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $item['itemLevel'] }}</td>
                                    <td class="px-3 py-2 text-right tabular-nums">{{ $item['requiredLevel'] ?: '—' }}</td>
                                    <td class="px-3 py-2">
                                        <div class="flex flex-wrap gap-x-3">
                                            @foreach ($item['stats'] as $key => $value)
                                                <span @class([
                                                    'whitespace-nowrap',
                                                    'font-semibold text-teal-700 dark:text-teal-400' => in_array($key, $filter->stats, true),
                                                    'text-red-600 dark:text-red-400' => $value < 0,
                                                ])>{{ ForeverStat::fromKey($key)->format($value, $ratingsPerPercent) }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <p class="mt-2 rounded-md border border-gray-200 px-3 py-8 text-center text-gray-500 dark:border-gray-800 dark:text-gray-400">No items match these filters.</p>
        @endforelse

        @if ($paginator->hasPages())
            <nav aria-label="Pagination" class="mt-6 flex items-center justify-between text-sm">
                @if ($paginator->onFirstPage())
                    <span class="text-gray-400 dark:text-gray-600">← Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="underline underline-offset-4 hover:text-primary-500 dark:hover:text-primary-400">← Previous</a>
                @endif

                <span class="text-gray-500 dark:text-gray-400">Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="underline underline-offset-4 hover:text-primary-500 dark:hover:text-primary-400">Next →</a>
                @else
                    <span class="text-gray-400 dark:text-gray-600">Next →</span>
                @endif
            </nav>
        @endif
    </div>
</x-layout>
