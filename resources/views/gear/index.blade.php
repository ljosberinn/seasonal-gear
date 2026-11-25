@php
    use App\Gear\GearCatalog;
    use App\Gear\Slot;

    $statLabels = ['crit' => 'Critical Strike', 'haste' => 'Haste', 'mastery' => 'Mastery', 'vers' => 'Versatility'];
    $shortStatLabels = ['sta' => 'Sta', 'crit' => 'Crit', 'haste' => 'Haste', 'mastery' => 'Mastery', 'vers' => 'Vers'];
    $query = request()->query();
    $formAction = $classKey === null ? route('gear.index') : route('gear.class', array_filter(['class' => $classKey, 'spec' => $specKey]));
    $title = collect([$classKey ? $classes[$classKey]['name'] : null, $specKey ? $classes[$classKey]['specs'][$specKey]['name'] : null])->filter()->reverse()->implode(' ');
@endphp

<x-layout :title="$title ?: null">
    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <header class="flex flex-wrap items-baseline justify-between gap-2">
            <h1 class="text-3xl font-semibold tracking-tight">{{ $meta['season']['name'] }} gear</h1>
            <p class="text-sm text-zinc-500">Build {{ $meta['build'] }}</p>
        </header>

        <nav class="mt-6 flex flex-wrap gap-2 text-sm" aria-label="Class">
            <a href="{{ route('gear.index', $query) }}" @class(['rounded-full px-3 py-1 ring-1 ring-zinc-300 dark:ring-zinc-700', 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' => $classKey === null])>All classes</a>
            @foreach ($classes as $key => $class)
                <a href="{{ route('gear.class', ['class' => $key] + $query) }}" @class(['rounded-full px-3 py-1 ring-1 ring-zinc-300 dark:ring-zinc-700', 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' => $classKey === $key])>{{ $class['name'] }}</a>
            @endforeach
        </nav>

        @if ($classKey !== null)
            <nav class="mt-3 flex flex-wrap gap-2 text-sm" aria-label="Specialization">
                <a href="{{ route('gear.class', ['class' => $classKey] + $query) }}" @class(['rounded-full px-3 py-1 ring-1 ring-zinc-300 dark:ring-zinc-700', 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' => $specKey === null])>All specs</a>
                @foreach ($classes[$classKey]['specs'] as $key => $spec)
                    <a href="{{ route('gear.class', ['class' => $classKey, 'spec' => $key] + $query) }}" @class(['rounded-full px-3 py-1 ring-1 ring-zinc-300 dark:ring-zinc-700', 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' => $specKey === $key])>{{ $spec['name'] }}</a>
                @endforeach
            </nav>
        @endif

        <form method="get" action="{{ $formAction }}" class="mt-6 grid gap-4 rounded-lg bg-white p-4 ring-1 ring-zinc-200 sm:grid-cols-[12rem_1fr_1fr_auto] dark:bg-zinc-900 dark:ring-zinc-800">
            <label class="flex flex-col gap-1 text-sm">
                <span class="font-medium">Slot</span>
                <select name="slot" class="rounded-md border-zinc-300 bg-transparent py-1.5 dark:border-zinc-700">
                    <option value="">Any slot</option>
                    @foreach (Slot::cases() as $slot)
                        <option value="{{ $slot->value }}" @selected($filter->slot === $slot)>{{ $slot->label() }}</option>
                    @endforeach
                </select>
            </label>

            <fieldset class="text-sm">
                <legend class="font-medium">Secondary stats (all of)</legend>
                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    @foreach ($statLabels as $key => $label)
                        <label class="flex items-center gap-1.5">
                            <input type="checkbox" name="stats[]" value="{{ $key }}" @checked(in_array($key, $filter->stats, true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="text-sm">
                <legend class="font-medium">Sources (none checked = all)</legend>
                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    @foreach (GearCatalog::SOURCE_TYPES as $key => $label)
                        <label class="flex items-center gap-1.5">
                            <input type="checkbox" name="sources[]" value="{{ $key }}" @checked(in_array($key, $filter->sources, true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-md bg-zinc-900 px-4 py-1.5 text-sm font-medium text-white dark:bg-zinc-100 dark:text-zinc-900">Filter</button>
                <a href="{{ $formAction }}" class="px-2 py-1.5 text-sm text-zinc-500 hover:underline">Reset</a>
            </div>
        </form>

        <p class="mt-6 text-sm text-zinc-500">{{ count($items) }} {{ Str::plural('item', count($items)) }}</p>

        <div class="mt-2 overflow-x-auto rounded-lg ring-1 ring-zinc-200 dark:ring-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 bg-white text-sm dark:divide-zinc-800 dark:bg-zinc-900">
                <thead class="text-left text-xs uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Item</th>
                        <th class="px-3 py-2">Slot</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2 text-right">ilvl</th>
                        <th class="px-3 py-2">Stats</th>
                        <th class="px-3 py-2">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($items as $item)
                        <tr class="align-top">
                            <td class="px-3 py-2">
                                <a href="https://www.wowhead.com/item={{ $item['id'] }}{{ $item['itemLevel'] ? '?ilvl='.$item['itemLevel'] : '' }}" @class([
                                    'font-medium hover:underline',
                                    'text-purple-700 dark:text-purple-400' => $item['quality'] === 4,
                                    'text-blue-700 dark:text-blue-400' => $item['quality'] === 3,
                                    'text-green-700 dark:text-green-400' => $item['quality'] === 2,
                                ]) target="_blank" rel="noopener">{{ $item['name'] }}</a>
                                @if ($item['itemSet'])
                                    <div class="text-xs text-zinc-500">{{ $item['itemSet']['name'] }}</div>
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
                                    <span class="text-zinc-500">Secondary stats of your choice</span>
                                @endif
                                @if ($item['sockets'] || $item['onUse'] || $item['onEquip'])
                                    <div class="text-xs text-zinc-500">
                                        {{ collect([
                                            $item['sockets'] ? Str::plural('socket', $item['sockets'], prependCount: true) : null,
                                            $item['onUse'] ? 'on use' : null,
                                            $item['onEquip'] ? 'on equip' : null,
                                        ])->filter()->implode(' · ') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @foreach ($item['sources'] as $source)
                                    <div class="whitespace-nowrap">
                                        <span class="text-zinc-500">{{ GearCatalog::SOURCE_TYPES[$source['type']] }}</span>
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
                            <td colspan="6" class="px-3 py-8 text-center text-zinc-500">No items match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</x-layout>
