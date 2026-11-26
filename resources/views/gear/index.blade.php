@php
    use App\Gear\GearCatalog;
    use App\Gear\Slot;

    // full class strings so Tailwind picks them up
    $statChips = [
        'crit' => ['label' => 'Critical Strike', 'dot' => 'bg-red-500', 'active' => 'border-red-500 bg-red-500/10 ring-red-500 dark:bg-red-950/60'],
        'haste' => ['label' => 'Haste', 'dot' => 'bg-amber-500', 'active' => 'border-amber-500 bg-amber-500/10 ring-amber-500 dark:bg-amber-950/60'],
        'mastery' => ['label' => 'Mastery', 'dot' => 'bg-purple-500', 'active' => 'border-purple-500 bg-purple-500/10 ring-purple-500 dark:bg-purple-950/60'],
        'vers' => ['label' => 'Versatility', 'dot' => 'bg-green-500', 'active' => 'border-green-500 bg-green-500/10 ring-green-500 dark:bg-green-950/60'],
    ];
    $shortStatLabels = ['sta' => 'Sta', 'crit' => 'Crit', 'haste' => 'Haste', 'mastery' => 'Mastery', 'vers' => 'Vers'];
    $query = request()->query();
    $formAction = $classKey === null ? route('gear.index') : route('gear.class', array_filter(['class' => $classKey, 'spec' => $specKey]));
    $hasFilters = $filter->slot !== null || $filter->stats !== [] || $filter->sources !== [];
    $title = collect([$classKey ? $classes[$classKey]['name'] : null, $specKey ? $classes[$classKey]['specs'][$specKey]['name'] : null])->filter()->reverse()->implode(' ');
@endphp

<x-layout :title="$title ?: null">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h1 class="text-3xl font-bold tracking-tight">{{ $meta['season']['name'] }} Gear</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Build {{ $meta['build'] }}</p>
        </div>

        <nav class="mt-6 flex flex-wrap gap-2" aria-label="Class">
            <x-filter.chip :href="route('gear.index', $query)" :active="$classKey === null">All Classes</x-filter.chip>
            @foreach ($classes as $key => $class)
                <x-filter.chip :href="route('gear.class', ['class' => $key] + $query)" :active="$classKey === $key" :color="$class['color']" :icon="$class['icon']">{{ $class['name'] }}</x-filter.chip>
            @endforeach
        </nav>

        @if ($classKey !== null)
            <nav class="mt-2 flex flex-wrap gap-2" aria-label="Specialization">
                <x-filter.chip :href="route('gear.class', ['class' => $classKey] + $query)" :active="$specKey === null" :color="$classes[$classKey]['color']" :icon="$classes[$classKey]['icon']">All Specs</x-filter.chip>
                @foreach ($classes[$classKey]['specs'] as $key => $spec)
                    <x-filter.chip :href="route('gear.class', ['class' => $classKey, 'spec' => $key] + $query)" :active="$specKey === $key" :color="$classes[$classKey]['color']" :icon="$spec['icon']">{{ $spec['name'] }}</x-filter.chip>
                @endforeach
            </nav>
        @endif

        <form method="get" action="{{ $formAction }}" data-auto-submit class="z-10 mt-6 flex flex-col gap-3 rounded-md border border-gray-200 bg-white/90 p-3 shadow-sm backdrop-blur lg:sticky lg:top-0 dark:border-gray-800 dark:bg-gray-950/90">
            <div role="group" aria-label="Source" class="grid grid-cols-2 gap-1.5 sm:grid-cols-3 lg:grid-cols-5">
                <x-filter.card :href="request()->fullUrlWithoutQuery(['sources'])" :active="$filter->sources === []" :count="$counts['anySource']">All Sources</x-filter.card>
                @foreach (GearCatalog::SOURCE_TYPES as $key => $label)
                    <x-filter.card :active="in_array($key, $filter->sources, true)" :count="$counts['sources'][$key]">
                        <input type="checkbox" name="sources[]" value="{{ $key }}" class="sr-only" @checked(in_array($key, $filter->sources, true))>
                        {{ $label }}
                    </x-filter.card>
                @endforeach
            </div>

            <div role="group" aria-label="Slot and Secondary Stats" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <select name="slot" aria-label="Slot" @class([
                        'cursor-pointer appearance-none rounded-md border py-0.5 pr-8 pl-3 text-sm focus-visible:outline-2 focus-visible:outline-teal-500',
                        'border-teal-500 bg-teal-500/10 font-bold text-gray-950 ring-1 ring-teal-500 ring-inset dark:bg-teal-950/60 dark:text-white' => $filter->slot !== null,
                        'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-100' => $filter->slot === null,
                    ])>
                        <option value="">Any Slot ({{ $counts['anySlot'] }})</option>
                        @foreach (Slot::cases() as $slot)
                            <option value="{{ $slot->value }}" @selected($filter->slot === $slot)>{{ $slot->label() }} ({{ $counts['slots'][$slot->value] }})</option>
                        @endforeach
                    </select>
                    <x-icon.chevron-down class="pointer-events-none absolute top-1/2 right-2 size-4 -translate-y-1/2 text-gray-500 dark:text-gray-400" />
                </div>

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
        </form>

        <p class="mt-6 text-sm text-gray-500 dark:text-gray-400">{{ count($items) }} {{ Str::plural('item', count($items)) }}</p>

        <div class="mt-2 overflow-x-auto rounded-md border border-gray-200 dark:border-gray-800">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                <thead class="bg-gray-50 text-left text-xs tracking-wide text-gray-500 uppercase dark:bg-gray-900/40 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">Item</th>
                        <th class="px-3 py-2">Slot</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2 text-right whitespace-nowrap">Item Level</th>
                        <th class="px-3 py-2">Stats</th>
                        <th class="px-3 py-2">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($items as $item)
                        <tr class="align-top">
                            <td class="px-3 py-2">
                                <a href="https://www.wowhead.com/item={{ $item['id'] }}{{ $item['itemLevel'] ? '?ilvl='.$item['itemLevel'] : '' }}" @if ($item['itemLevel']) data-wowhead="ilvl={{ $item['itemLevel'] }}" @endif @class([
                                    'font-medium hover:underline',
                                    'text-purple-700 dark:text-purple-400' => $item['quality'] === 4,
                                    'text-blue-700 dark:text-blue-400' => $item['quality'] === 3,
                                    'text-green-700 dark:text-green-400' => $item['quality'] === 2,
                                ]) target="_blank" rel="noopener">{{ $item['name'] }}</a>
                                @if ($item['itemSet'])
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item['itemSet']['name'] }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ Slot::from($item['slot'])->label() }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ GearCatalog::typeLabel($item) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums">{{ $item['itemLevel'] ?? '–' }}</td>
                            <td class="px-3 py-2">
                                @if ($item['stats'])
                                    <div class="flex flex-wrap gap-x-3 tabular-nums">
                                        @foreach ($item['stats'] as $stat => $value)
                                            <span @class(['font-semibold' => in_array($stat, $filter->stats, true)])>
                                                {{ $value }} {{ $stat === 'primary' ? collect($item['primaries'])->map(fn ($primary) => ucfirst($primary))->implode('/') : $shortStatLabels[$stat] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @elseif ($item['choiceStats'])
                                    <span class="text-gray-500 dark:text-gray-400">Secondary Stats of Your Choice</span>
                                @endif
                                @if ($item['sockets'] || $item['onUse'] || $item['onEquip'])
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ collect([
                                            $item['sockets'] ? Str::plural('Socket', $item['sockets'], prependCount: true) : null,
                                            $item['onUse'] ? 'On Use' : null,
                                            $item['onEquip'] ? 'On Equip' : null,
                                        ])->filter()->implode(' · ') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @foreach ($item['sources'] as $source)
                                    <div class="whitespace-nowrap">
                                        <span class="text-gray-500 dark:text-gray-400">{{ GearCatalog::SOURCE_TYPES[$source['type']] }}</span>
                                        @if ($source['instance'])
                                            · {{ $source['instance'] }}
                                        @endif
                                        @if ($source['encounter'])
                                            · {{ $source['encounter'] }}
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">No items match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
