@php
    use App\Gear\GearCatalog;
    use App\Gear\GearStatistics;
    use App\Gear\Slot;
    use App\Gear\Stat;

    $profile = $instance['profile'];
    $armorTypes = ['Cloth', 'Leather', 'Mail', 'Plate'];
    $armorSlots = array_values(array_filter(Slot::cases(), fn (Slot $slot): bool => $slot->isArmor()));
@endphp

<x-layout :title="$instance['name'].' Statistics'" :build="$meta['build']">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <x-stats.back-link />

        <h1 class="mt-4 flex items-center gap-3 text-2xl font-semibold">
            <x-stats.instance-icon :icon="$instance['icon']" :icon-file-id="$instance['iconFileId']" class="size-8" />
            {{ $instance['name'] }}
        </h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">{{ GearCatalog::SOURCE_TYPES[$instance['type']] }}</p>

        <div class="mt-6 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <x-stats.tile label="Items" :value="$profile['items']" />
            <x-stats.tile label="Encounters" :value="count($instance['encounters'])" />
            <x-stats.tile label="With Fixed Secondaries" :value="$profile['rated']" />
        </div>

        <div class="grid gap-x-12 lg:grid-cols-2">
            <x-stats.section title="Secondary Stats">
                <x-slot:description>Share of this instance's items with fixed secondaries that carry each stat. The tick marks the share across all season items.</x-slot:description>
                <x-stats.bars reference-label="All season items" :rows="array_map(fn (string $stat): array => ['stat' => $stat, 'count' => $profile['presence'][$stat], 'share' => GearStatistics::share($profile, $stat), 'reference' => GearStatistics::share($overall['profile'], $stat)], Stat::SECONDARY_KEYS)" />
            </x-stats.section>

            <x-stats.section title="Stat Pairs">
                <x-slot:description>Which two secondaries this instance's items combine, against the share across all season items.</x-slot:description>
                <x-stats.bars reference-label="All season items" :rows="array_map(fn (string $pair): array => ['stat' => $pair, 'count' => $profile['pairs'][$pair], 'share' => GearStatistics::share($profile, $pair), 'reference' => GearStatistics::share($overall['profile'], $pair)], GearStatistics::pairKeys())" />
            </x-stats.section>
        </div>

        <x-stats.section title="Encounters">
            <x-slot:description>Items per encounter carrying each secondary. Darker cells hold a bigger part of that encounter's items.</x-slot:description>
            <div class="overflow-x-auto rounded-md border border-gray-200 dark:border-gray-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-xs tracking-wide text-gray-500 uppercase dark:bg-gray-900/40 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-left">Encounter</th>
                            <th class="px-2 py-2">Items</th>
                            @foreach (Stat::SECONDARY_KEYS as $stat)
                                <th class="px-2 py-2"><x-stats.stat-label :stat="$stat" class="normal-case" /></th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($instance['encounters'] as $encounter)
                            <tr>
                                <td class="px-3 py-1.5 whitespace-nowrap">{{ $encounter['name'] }}</td>
                                <td class="px-2 py-1.5 text-center tabular-nums">{{ $encounter['profile']['items'] }}</td>
                                @foreach (Stat::SECONDARY_KEYS as $stat)
                                    @php($count = $encounter['profile']['presence'][$stat])
                                    @if ($count === 0)
                                        <x-stats.heat-cell tone="none" value="·" class="text-gray-300 dark:text-gray-700" />
                                    @else
                                        <x-stats.heat-cell :value="$count" :intensity="$count / max(1, $encounter['profile']['rated'])" />
                                    @endif
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-stats.section>

        @if ($instance['armor'] !== [])
            <x-stats.section title="Armor">
                <x-slot:description>Armor pieces per slot and armor type. A highlighted 0 is a slot classes of that armor type can't get here.</x-slot:description>
                <div class="overflow-x-auto rounded-md border border-gray-200 sm:max-w-xl dark:border-gray-800">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-xs tracking-wide text-gray-500 uppercase dark:bg-gray-900/40 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2 text-left">Slot</th>
                                @foreach ($armorTypes as $armorType)
                                    <th class="px-2 py-2">{{ $armorType }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @php($armorMax = max(1, ...array_values(array_map(fn (array $types): int => max($types), $instance['armor']))))
                            @foreach ($armorSlots as $slot)
                                <tr>
                                    <td class="px-3 py-1.5">{{ $slot->label() }}</td>
                                    @foreach ($armorTypes as $armorType)
                                        @php($count = $instance['armor'][$slot->value][$armorType] ?? 0)
                                        @if ($count === 0)
                                            <x-stats.heat-cell tone="gap" :value="0" :title="'No '.$armorType.' '.$slot->label()" />
                                        @else
                                            <x-stats.heat-cell :value="$count" :intensity="$count / $armorMax" />
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-stats.section>
        @endif
    </div>
</x-layout>
