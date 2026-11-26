@php
    use App\Gear\GearCatalog;
    use App\Gear\GearStatistics;
    use App\Gear\Slot;
    use App\Gear\Stat;

    $spec = $class['specs'][$specKey];
    $profile = $statistics['profile'];
    $pairs = GearStatistics::pairKeys();
@endphp

<x-layout :title="$spec['name'].' '.$class['name'].' Statistics'" :build="$meta['build']">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <x-stats.back-link />

        <h1 class="mt-4 flex items-center gap-3 text-2xl font-semibold">
            <img src="https://wow.zamimg.com/images/wow/icons/medium/{{ $spec['icon'] }}.jpg" alt="" width="32" height="32" class="size-8 rounded-sm">
            {{ $spec['name'] }} {{ $class['name'] }}
        </h1>
        <p class="mt-2 max-w-3xl text-gray-600 dark:text-gray-400">
            Raid, tier set and dungeon items this spec can use.
            <a href="{{ route('gear.class', ['class' => $classKey, 'spec' => $specKey]) }}" class="underline underline-offset-4 hover:text-primary-500 dark:hover:text-primary-400">Browse them</a>.
        </p>

        <div class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-5">
            <x-stats.tile label="Items" :value="$profile['items']" />
            @foreach ($statistics['bySource'] as $type => $count)
                <x-stats.tile :label="GearCatalog::SOURCE_TYPES[$type]" :value="$count" />
            @endforeach
            <x-stats.tile label="With Fixed Secondaries" :value="$profile['rated']" />
        </div>

        <div class="grid gap-x-12 lg:grid-cols-2">
            <x-stats.section title="Secondary Stats">
                <x-slot:description>Share of this spec's items with fixed secondaries that carry each stat. The tick marks the share across all season items.</x-slot:description>
                <x-stats.bars reference-label="All season items" :rows="array_map(fn (string $stat): array => ['stat' => $stat, 'count' => $profile['presence'][$stat], 'share' => GearStatistics::share($profile, $stat), 'reference' => GearStatistics::share($overall['profile'], $stat)], Stat::SECONDARY_KEYS)" />
            </x-stats.section>

            <x-stats.section title="Stat Pairs">
                <x-slot:description>Which two secondaries this spec's items combine, against the share across all season items.</x-slot:description>
                <x-stats.bars reference-label="All season items" :rows="array_map(fn (string $pair): array => ['stat' => $pair, 'count' => $profile['pairs'][$pair], 'share' => GearStatistics::share($profile, $pair), 'reference' => GearStatistics::share($overall['profile'], $pair)], $pairs)" />
            </x-stats.section>
        </div>

        <x-stats.section title="Slots">
            <x-slot:description>Options per slot by source and by stat pair. Darker pair cells hold a bigger part of that slot's items. "Other" covers items with one secondary or none, which is most trinkets. A highlighted 0 is a slot season content leaves empty.</x-slot:description>
            <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-gray-900/40 dark:text-gray-400">
                        <tr class="tracking-wide uppercase">
                            <th class="px-3 py-2 text-left">Slot</th>
                            <th class="px-2 py-2">Items</th>
                            @foreach ($statistics['bySource'] as $type => $count)
                                <th class="px-2 py-2">{{ GearCatalog::SOURCE_TYPES[$type] }}</th>
                            @endforeach
                            @foreach ($pairs as $pair)
                                <th class="px-2 py-2 normal-case"><x-stats.stat-label :stat="$pair" class="font-normal" /></th>
                            @endforeach
                            <th class="px-2 py-2">Other</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($statistics['slots'] as $slot => $row)
                            <tr>
                                <td class="px-3 py-1.5 whitespace-nowrap">{{ Slot::from($slot)->label() }}</td>
                                @if ($row['items'] === 0)
                                    <x-stats.heat-cell tone="gap" :value="0" :title="'No season '.Slot::from($slot)->label()" />
                                @else
                                    <td class="px-2 py-1.5 text-center font-semibold tabular-nums">{{ $row['items'] }}</td>
                                @endif
                                @foreach ($row['bySource'] as $count)
                                    <td class="px-2 py-1.5 text-center text-gray-600 tabular-nums dark:text-gray-400">{{ $count }}</td>
                                @endforeach
                                @foreach ([...$row['pairs'], 'other' => $row['other']] as $count)
                                    @if ($count === 0)
                                        <x-stats.heat-cell tone="none" value="·" class="text-gray-300 dark:text-gray-700" />
                                    @else
                                        <x-stats.heat-cell :value="$count" :intensity="$count / max(1, $row['items'])" />
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-stats.section>
    </div>
</x-layout>
