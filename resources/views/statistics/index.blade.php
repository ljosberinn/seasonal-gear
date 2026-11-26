@php
    use App\Gear\GearCatalog;
    use App\Gear\GearStatistics;
    use App\Gear\Slot;
    use App\Gear\Stat;

    $profile = $overall['profile'];
    // a share this many points off the overall one gets the full diverging color
    $fullDeviation = 0.2;
    $maxItems = max(array_map(fn (array $spec): int => $spec['profile']['items'], $specs));
    $slots = array_values(array_filter(Slot::cases(), fn (Slot $slot): bool => collect($specs)->contains(fn (array $spec): bool => $spec['coverage'][$slot->value] !== null)));
    $slotMax = collect($slots)->mapWithKeys(fn (Slot $slot): array => [$slot->value => max(1, ...array_map(fn (array $spec): int => $spec['coverage'][$slot->value] ?? 0, $specs))]);
    $shareCell = fn (array $rowProfile, string $stat): array => [
        'value' => round(GearStatistics::share($rowProfile, $stat) * 100).'%',
        'intensity' => (GearStatistics::share($rowProfile, $stat) - GearStatistics::share($profile, $stat)) / $fullDeviation,
        'title' => GearStatistics::STAT_NAMES[$stat].': '.$rowProfile['presence'][$stat].' of '.$rowProfile['rated'].' items, '.round(GearStatistics::share($profile, $stat) * 100).'% overall',
    ];
@endphp

<x-layout title="Statistics" :build="$meta['build']">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <h1 class="text-2xl font-semibold">Statistics</h1>
        <p class="mt-2 max-w-3xl text-gray-600 dark:text-gray-400">
            How {{ $meta['season']['name'] }} raid, tier set and dungeon loot is spread across specs, slots and secondary stats.
            Delve, world and crafted gear is left out, since it either stops below Myth or lets you pick the stats.
        </p>

        <div class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-5">
            <x-stats.tile label="Items" :value="$profile['items']" />
            @foreach ($overall['bySource'] as $type => $count)
                <x-stats.tile :label="GearCatalog::SOURCE_TYPES[$type]" :value="$count" />
            @endforeach
            <x-stats.tile label="With Fixed Secondaries" :value="$profile['rated']" />
        </div>

        @if ($highlights !== [])
            <ul class="mt-6 max-w-3xl list-disc space-y-1 pl-5 text-sm">
                @foreach ($highlights as $highlight)
                    <li>{{ $highlight }}</li>
                @endforeach
            </ul>
        @endif

        <div class="grid gap-x-12 lg:grid-cols-2">
            <x-stats.section title="Secondary Stats">
                <x-slot:description>Share of items with fixed secondaries that carry each stat. Items have two secondaries, so an even pool puts every stat at 50%.</x-slot:description>
                <x-stats.bars reference-label="Even split" :rows="array_map(fn (string $stat): array => ['stat' => $stat, 'count' => $profile['presence'][$stat], 'share' => GearStatistics::share($profile, $stat), 'reference' => 0.5], Stat::SECONDARY_KEYS)" />
            </x-stats.section>

            <x-stats.section title="Stat Pairs">
                <x-slot:description>Which two secondaries appear together. There are six pairs, so an even pool gives each about 17%.</x-slot:description>
                <x-stats.bars reference-label="Even split" :rows="array_map(fn (string $pair): array => ['stat' => $pair, 'count' => $profile['pairs'][$pair], 'share' => GearStatistics::share($profile, $pair), 'reference' => 1 / 6], GearStatistics::pairKeys())" />
            </x-stats.section>
        </div>

        <x-stats.section title="Specs">
            <x-slot:description>Items each spec can use, and how often each secondary shows up for it compared to all season items. Armor type, primary stat and weapon restrictions drive the differences. Pick a spec for its slot-by-slot breakdown.</x-slot:description>
            <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs tracking-wide text-gray-500 uppercase dark:bg-gray-900/40 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-left">Spec</th>
                            <th class="px-3 py-2 text-left">Items</th>
                            @foreach ($overall['bySource'] as $type => $count)
                                <th class="px-2 py-2">{{ GearCatalog::SOURCE_TYPES[$type] }}</th>
                            @endforeach
                            @foreach (Stat::SECONDARY_KEYS as $stat)
                                <th class="px-2 py-2"><x-stats.stat-label :stat="$stat" class="normal-case" /></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($specs as $spec)
                            <tr>
                                <td class="px-3 py-1.5 whitespace-nowrap">
                                    <a href="{{ route('statistics.spec', ['class' => $spec['classKey'], 'spec' => $spec['specKey']]) }}" class="inline-flex items-center gap-2 hover:underline">
                                        <img src="https://wow.zamimg.com/images/wow/icons/medium/{{ $spec['icon'] }}.jpg" alt="" width="20" height="20" class="size-5 rounded-sm">
                                        <span>{{ $spec['specName'] }} <span class="text-gray-500 dark:text-gray-400">{{ $spec['className'] }}</span></span>
                                    </a>
                                </td>
                                <td class="px-3 py-1.5" title="{{ $spec['profile']['items'] }} items">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2.5 w-24 shrink-0"><div class="h-full rounded-r bg-(--viz-series)" style="width: {{ $spec['profile']['items'] / $maxItems * 100 }}%"></div></div>
                                        <span class="tabular-nums">{{ $spec['profile']['items'] }}</span>
                                    </div>
                                </td>
                                @foreach ($spec['bySource'] as $count)
                                    <td class="px-2 py-1.5 text-center text-gray-600 tabular-nums dark:text-gray-400">{{ $count }}</td>
                                @endforeach
                                @foreach (Stat::SECONDARY_KEYS as $stat)
                                    @php($cell = $shareCell($spec['profile'], $stat))
                                    <x-stats.heat-cell tone="diverging" :value="$cell['value']" :intensity="$cell['intensity']" :title="$cell['title']" />
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-stats.diverging-legend />
        </x-stats.section>

        <x-stats.section title="Slot Coverage">
            <x-slot:description>Season items per slot for each spec. Darker cells have more options than other specs get for that slot. A highlighted 0 is a slot season content leaves empty while delves, world drops or crafting do cover it; a dash is a slot the spec can't use.</x-slot:description>
            <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-left tracking-wide uppercase">Spec</th>
                            @foreach ($slots as $slot)
                                <th class="px-2 py-2 font-medium whitespace-nowrap">{{ $slot->label() }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($specs as $spec)
                            <tr>
                                <td class="px-3 py-1 whitespace-nowrap">
                                    <a href="{{ route('statistics.spec', ['class' => $spec['classKey'], 'spec' => $spec['specKey']]) }}" class="hover:underline">{{ $spec['specName'] }} <span class="text-gray-500 dark:text-gray-400">{{ $spec['className'] }}</span></a>
                                </td>
                                @foreach ($slots as $slot)
                                    @php($count = $spec['coverage'][$slot->value])
                                    @if ($count === null)
                                        <x-stats.heat-cell tone="none" value="–" class="text-gray-400 dark:text-gray-600" :title="$spec['specName'].' can\'t use '.$slot->label()" />
                                    @elseif ($count === 0)
                                        <x-stats.heat-cell tone="gap" :value="0" :title="'No season '.$slot->label().' for '.$spec['specName'].' '.$spec['className']" />
                                    @else
                                        <x-stats.heat-cell :value="$count" :intensity="$count / $slotMax[$slot->value]" :title="$count.' '.$slot->label().' options'" />
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-stats.section>

        <x-stats.section title="Instances">
            <x-slot:description>How each instance's loot compares to all season items, colored like the spec table. Tier pieces count toward the raid. Small instances swing on a single item, so read their colors loosely.</x-slot:description>
            <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs tracking-wide text-gray-500 uppercase dark:bg-gray-900/40 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-left">Instance</th>
                            <th class="px-3 py-2 text-left">Type</th>
                            <th class="px-2 py-2">Items</th>
                            @foreach (Stat::SECONDARY_KEYS as $stat)
                                <th class="px-2 py-2"><x-stats.stat-label :stat="$stat" class="normal-case" /></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($instances as $instance)
                            <tr>
                                <td class="px-3 py-1.5 whitespace-nowrap"><a href="{{ route('statistics.instance', $instance['id']) }}" class="hover:underline">{{ $instance['name'] }}</a></td>
                                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400">{{ GearCatalog::SOURCE_TYPES[$instance['type']] }}</td>
                                <td class="px-2 py-1.5 text-center tabular-nums">{{ $instance['profile']['items'] }}</td>
                                @foreach (Stat::SECONDARY_KEYS as $stat)
                                    @php($cell = $shareCell($instance['profile'], $stat))
                                    <x-stats.heat-cell tone="diverging" :value="$cell['value']" :intensity="$cell['intensity']" :title="$cell['title']" />
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-stats.diverging-legend />
        </x-stats.section>
    </div>
</x-layout>
