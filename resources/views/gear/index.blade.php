@php
    use App\Gear\GearCatalog;
    use App\Gear\Slot;

    // full class strings so Tailwind picks them up
    $statChips = [
        'crit' => ['label' => 'Critical Strike', 'short' => 'Crit', 'text' => 'text-red-600 dark:text-red-400', 'dot' => 'bg-red-500', 'active' => 'border-red-500 bg-red-500/10 ring-red-500 dark:bg-red-950/60'],
        'haste' => ['label' => 'Haste', 'short' => 'Haste', 'text' => 'text-amber-600 dark:text-amber-400', 'dot' => 'bg-amber-500', 'active' => 'border-amber-500 bg-amber-500/10 ring-amber-500 dark:bg-amber-950/60'],
        'mastery' => ['label' => 'Mastery', 'short' => 'Mastery', 'text' => 'text-purple-600 dark:text-purple-400', 'dot' => 'bg-purple-500', 'active' => 'border-purple-500 bg-purple-500/10 ring-purple-500 dark:bg-purple-950/60'],
        'vers' => ['label' => 'Versatility', 'short' => 'Vers', 'text' => 'text-green-600 dark:text-green-400', 'dot' => 'bg-green-500', 'active' => 'border-green-500 bg-green-500/10 ring-green-500 dark:bg-green-950/60'],
    ];
    $query = request()->query();
    $formAction = $classKey === null ? route('gear.index') : route('gear.class', array_filter(['class' => $classKey, 'spec' => $specKey]));
    $allSources = array_keys(GearCatalog::SOURCE_TYPES);
    $showsAllSources = array_diff($allSources, $filter->sources) === [];
    $hasFilters = $filter->slot !== null || $filter->track !== null || $filter->itemSet !== null || $filter->stats !== [] || collect($filter->sources)->sort()->values()->all() !== collect(GearCatalog::DEFAULT_SOURCE_TYPES)->sort()->values()->all();
    // slot, stats and other sources would hide pieces of the set
    $itemSetUrl = fn (int $id): string => $formAction.'?'.Arr::query(['set' => $id, 'sources' => $allSources] + Arr::except($query, ['set', 'sources', 'slot', 'stats']));
    $specOptionsFor = fn (string $key): array => [
        ['href' => route('gear.class', ['class' => $key] + $query), 'label' => 'All Specs', 'active' => $classKey === $key && $specKey === null, 'icon' => $classes[$key]['icon'], 'color' => $classes[$key]['color']],
        ...collect($classes[$key]['specs'])->map(fn (array $spec, string $specKeyOption): array => [
            'href' => route('gear.class', ['class' => $key, 'spec' => $specKeyOption] + $query),
            'label' => $spec['name'],
            'active' => $classKey === $key && $specKey === $specKeyOption,
            'icon' => $spec['icon'],
            'color' => $classes[$key]['color'],
        ])->values(),
    ];
    $classOptions = [
        ['href' => route('gear.index', $query), 'label' => 'All Classes', 'cardLabel' => 'All Classes', 'active' => $classKey === null, 'icon' => 'inv_misc_questionmark', 'cardIcon' => 'inv_misc_questionmark', 'color' => null, 'specs' => []],
        ...collect($classes)->sortBy('name')->map(fn (array $class, string $key): array => [
            'href' => route('gear.class', ['class' => $key] + $query),
            'label' => $class['name'],
            // without a spec row on wider screens, the active class card names the picked spec
            'cardLabel' => $classKey === $key && $specKey !== null ? $class['specs'][$specKey]['name'] : $class['name'],
            'active' => $classKey === $key,
            'icon' => $class['icon'],
            'cardIcon' => $classKey === $key && $specKey !== null ? $class['specs'][$specKey]['icon'] : $class['icon'],
            'color' => $class['color'],
            'specs' => $specOptionsFor($key),
        ])->values(),
    ];
    $specOptions = $classKey === null ? [] : $specOptionsFor($classKey);
    // a picked slot already implies the type, and slots like neck or back have none
    $showsType = $filter->slot === null && collect($items)->contains(fn (array $item): bool => GearCatalog::typeLabel($item) !== '');
    $exportRows = collect($items)->map(fn (array $item): array => [
        'id' => $item['id'],
        'name' => $item['name'],
        'slot' => Slot::from($item['slot'])->exportKey(),
        // the upgrade rank's item level, which the Wowhead tooltip links render at
        'max_itemlevel' => $item['rank']['itemLevel'] ?? null,
    ]);
    $title = collect([$classKey ? $classes[$classKey]['name'] : null, $specKey ? $classes[$classKey]['specs'][$specKey]['name'] : null])->filter()->reverse()->implode(' ');
@endphp

<x-layout :title="$title ?: null" :build="$meta['build']" :preview="$preview">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <h1 class="sr-only">{{ $meta['season']['name'] }} Gear</h1>

        <nav aria-label="Class and Specialization">
            {{-- phones pick class and spec from two dropdowns, wider screens from cards whose spec flyout opens on hover --}}
            @foreach (['Class' => $classOptions, 'Specialization' => $specOptions] as $label => $options)
                @if ($options !== [])
                    @php($selected = collect($options)->firstWhere('active', true))
                    <x-filter.nav-select @class(['sm:hidden', 'mt-1.5' => ! $loop->first]) :label="$label" :options="$options" :icon="$selected['icon']" :color="$selected['color']" />
                @endif
            @endforeach

            <ul data-flyout-container class="hidden grid-cols-4 gap-1.5 sm:grid lg:grid-cols-7">
                @foreach ($classOptions as $index => $option)
                    <li data-flyout class="relative">
                        <x-filter.card data-flyout-trigger class="h-full" :aria-controls="$option['specs'] === [] ? null : 'specs-'.$index" :aria-expanded="$option['specs'] === [] ? null : 'false'" :href="$option['href']" :active="$option['active']" :icon="$option['cardIcon']" :color="$option['color']">{{ $option['cardLabel'] }}</x-filter.card>
                        @if ($option['specs'] !== [])
                            <div id="specs-{{ $index }}" data-flyout-panel hidden class="absolute top-full left-0 z-20 w-56 pt-1.5">
                                <ul class="grid gap-1 rounded-md border border-gray-200 bg-white p-1.5 shadow-lg dark:border-gray-800 dark:bg-gray-950">
                                    @foreach ($option['specs'] as $spec)
                                        <li><x-filter.card :href="$spec['href']" :active="$spec['active']" :icon="$spec['icon']" :color="$spec['color']">{{ $spec['label'] }}</x-filter.card></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        <form method="get" action="{{ $formAction }}" data-auto-submit class="z-10 mt-6 flex flex-col gap-3 rounded-md border border-gray-200 bg-white/90 p-3 shadow-sm backdrop-blur lg:sticky lg:top-0 dark:border-gray-800 dark:bg-gray-950/90">
            <div role="group" aria-label="Source" class="grid grid-cols-2 gap-1.5 sm:grid-cols-4 lg:grid-cols-7">
                <x-filter.card :href="request()->fullUrlWithQuery(['sources' => $allSources])" :active="$showsAllSources" :count="$counts['anySource']">All Sources</x-filter.card>
                @foreach (GearCatalog::SOURCE_TYPES as $key => $label)
                    <x-filter.card :active="in_array($key, $filter->sources, true)" :count="$counts['sources'][$key]">
                        <input type="checkbox" name="sources[]" value="{{ $key }}" class="sr-only" @checked(in_array($key, $filter->sources, true))>
                        {{ $label }}
                    </x-filter.card>
                @endforeach
            </div>

            <div role="group" aria-label="Slot and Secondary Stats" class="flex flex-wrap items-center gap-2">
                <x-filter.select name="slot" aria-label="Slot" :active="$filter->slot !== null">
                    <option value="">Any Slot ({{ $counts['anySlot'] }})</option>
                    @foreach (Slot::cases() as $slotOption)
                        <option value="{{ $slotOption->value }}" @selected($filter->slot === $slotOption)>{{ $slotOption->label() }} ({{ $counts['slots'][$slotOption->value] }})</option>
                    @endforeach
                </x-filter.select>

                <x-filter.divider />

                <x-filter.chip :href="request()->fullUrlWithoutQuery(['stats'])" :active="$filter->stats === []" :count="$counts['anyStat']">Any Stats</x-filter.chip>

                <x-filter.divider />

                @foreach ($statChips as $key => $chip)
                    <x-filter.chip :active="in_array($key, $filter->stats, true)" :count="$counts['stats'][$key]" :dot="$chip['dot']" :active-color="$chip['active']">
                        <input type="checkbox" name="stats[]" value="{{ $key }}" class="sr-only" @checked(in_array($key, $filter->stats, true))>
                        {{ $chip['label'] }}
                    </x-filter.chip>
                @endforeach

                @if ($hasFilters)
                    <a href="{{ $formAction }}" class="ml-auto text-sm text-gray-500 underline underline-offset-4 hover:text-primary-500 dark:text-gray-400 dark:hover:text-primary-400">Reset Filters</a>
                @endif

                <noscript>
                    <button type="submit" class="rounded-md bg-teal-600 px-3 py-0.5 text-sm font-bold text-white">Apply Filters</button>
                </noscript>
            </div>

            <div role="group" aria-label="Upgrade Track and Item Set" class="flex flex-wrap items-center gap-2">
                {{-- picks the upgrade rank Wowhead renders; items off that track fall back to their nearest one --}}
                <x-filter.select name="track" aria-label="Upgrade Track" :active="$filter->track !== null">
                    <option value="">Highest Track</option>
                    @foreach ($tracks as $key => $name)
                        <option value="{{ $key }}" @selected($filter->track === $key)>{{ $name }}</option>
                    @endforeach
                </x-filter.select>

                <x-filter.select name="set" aria-label="Item Set" :active="$filter->itemSet !== null">
                    <option value="">With or Without Set ({{ $counts['anyItemSet'] }})</option>
                    <option value="any" @selected($filter->itemSet === true)>Has Set ({{ $counts['hasItemSet'] }})</option>
                    <optgroup label="Sets">
                        @foreach ($itemSets as $id => $name)
                            {{-- most sets are another class's tier, which only clutters the list --}}
                            @if ($counts['itemSets'][$id] > 0 || $filter->itemSet === $id)
                                <option value="{{ $id }}" @selected($filter->itemSet === $id)>{{ $name }} ({{ $counts['itemSets'][$id] }})</option>
                            @endif
                        @endforeach
                    </optgroup>
                </x-filter.select>
            </div>
        </form>

        @if ($specKey !== null)
            <x-archon-banner class="mt-6" :class-key="$classKey" :spec-key="$specKey" :player-class="$classes[$classKey]" />
        @endif

        <div data-flyout-container class="mt-6 flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ count($items) }} {{ Str::plural('item', count($items)) }}</p>

            @if ($items !== [])
                <div data-flyout data-export="{{ collect(['seasonal-gear', $classKey, $specKey])->filter()->implode('-') }}" class="relative">
                    <button type="button" data-flyout-trigger aria-controls="export-formats" aria-expanded="false" class="flex cursor-pointer items-center gap-1 rounded-md border border-gray-200 bg-gray-50 px-2.5 py-0.5 text-sm text-gray-600 hover:border-gray-300 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-teal-500 dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-100">
                        Export
                        <x-icon.chevron-down class="size-4" />
                    </button>
                    <div id="export-formats" data-flyout-panel hidden class="absolute top-full left-0 z-20 w-32 pt-1.5">
                        <ul class="grid gap-1 rounded-md border border-gray-200 bg-white p-1.5 shadow-lg dark:border-gray-800 dark:bg-gray-950">
                            @foreach (['json' => 'JSON', 'csv' => 'CSV'] as $format => $label)
                                <li>
                                    <button type="button" data-export-format="{{ $format }}" class="w-full cursor-pointer rounded-md border border-gray-200 bg-gray-50 px-2.5 py-1.5 text-left text-sm text-gray-600 hover:border-gray-300 hover:text-gray-900 focus-visible:outline-2 focus-visible:outline-teal-500 dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-100">{{ $label }}</button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <script type="application/json" data-export-rows>@json($exportRows)</script>
                </div>
            @endif
        </div>

        <div class="mt-2 overflow-x-auto rounded-md border border-gray-200 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                <thead class="bg-gray-50 text-left text-xs tracking-wide text-gray-500 uppercase dark:bg-gray-900/40 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Item</th>
                        @if ($filter->slot === null)
                            <th class="px-3 py-2">Slot</th>
                        @endif
                        @if ($showsType)
                            <th class="px-3 py-2">Type</th>
                        @endif
                        <th class="px-3 py-2">Stats</th>
                        <th class="px-3 py-2">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 *:even:bg-gray-50 dark:*:even:bg-gray-900/40">
                    @forelse ($items as $item)
                        <tr>
                            <td class="px-3 py-2">
                                @php($wowheadParams = GearCatalog::wowheadParams($item))
                                {{-- the item link is `contents` so the set link can sit below the name without nesting links, while Wowhead's icon still spans every text row --}}
                                <div class="grid grid-cols-[auto_1fr] items-center gap-x-2 [&_.iconmedium]:row-span-3">
                                    <a href="https://www.wowhead.com/item={{ $item['id'] }}{{ $wowheadParams ? '?'.$wowheadParams : '' }}" @if ($wowheadParams) data-wowhead="{{ $wowheadParams }}" @endif class="group contents" target="_blank" rel="noopener">
                                        {{-- Wowhead's script prepends the item icon in here --}}
                                        <span @class([
                                            'col-start-2 justify-self-start font-medium group-hover:underline group-focus-visible:underline',
                                            'text-purple-700 dark:text-purple-400' => $item['quality'] === 4,
                                            'text-blue-700 dark:text-blue-400' => $item['quality'] === 3,
                                            'text-green-700 dark:text-green-400' => $item['quality'] === 2,
                                        ])>{{ $item['name'] }}</span>
                                    </a>
                                    @if ($item['itemSet'])
                                        <a href="{{ $itemSetUrl($item['itemSet']['id']) }}" title="Show only this set" class="col-start-2 justify-self-start text-xs text-gray-500 hover:text-primary-500 hover:underline dark:text-gray-400 dark:hover:text-primary-400">{{ $item['itemSet']['name'] }}</a>
                                    @endif
                                    @if ($item['embellished'])
                                        <span class="col-start-2 text-xs text-gray-500 dark:text-gray-400">Embellished</span>
                                    @endif
                                </div>
                            </td>
                            @if ($filter->slot === null)
                                <td class="px-3 py-2 whitespace-nowrap">{{ Slot::from($item['slot'])->label() }}</td>
                            @endif
                            @if ($showsType)
                                <td class="px-3 py-2 whitespace-nowrap">{{ GearCatalog::typeLabel($item) }}</td>
                            @endif
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-x-3">
                                    @if ($item['choiceStats'])
                                        <span class="text-gray-500 dark:text-gray-400">Your Choice</span>
                                    @elseif ($item['secondaries'] !== [])
                                        <span class="whitespace-nowrap">
                                            @foreach (GearCatalog::rankSecondaries($item['secondaries']) as $ranked)
                                                @if ($ranked['relation'])
                                                    <span class="text-gray-500 dark:text-gray-400">{{ $ranked['relation'] }}</span>
                                                @endif
                                                <span @class([$statChips[$ranked['stat']]['text'], 'font-semibold' => in_array($ranked['stat'], $filter->stats, true)])>{{ $statChips[$ranked['stat']]['short'] }}</span>
                                            @endforeach
                                        </span>
                                    @endif
                                </div>
                                @if ($item['sockets'] || $item['onUse'] || $item['onEquip'])
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ collect([
                                            $item['sockets'] ? Str::plural('Socket', $item['sockets'], prependCount: true) : null,
                                            $item['onUse'] ? 'On Use' : null,
                                            $item['onEquip'] ? 'On Equip' : null,
                                        ])->filter()->implode(' | ') }}
                                    </div>
                                @endif
                            </td>
                            <td class="space-y-1 px-3 py-2">
                                @foreach ($item['sources'] as $source)
                                    <div class="whitespace-nowrap">
                                        {{-- without a boss, the source type (Tier Set, World, Crafted, …) is the most specific source --}}
                                        <div>{{ $source['encounter'] ?? GearCatalog::SOURCE_TYPES[$source['type']] }}</div>
                                        @if ($source['instance'])
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $source['instance'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + ($filter->slot === null ? 1 : 0) + ($showsType ? 1 : 0) }}" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">No items match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
