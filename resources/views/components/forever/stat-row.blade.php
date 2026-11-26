@props([
    'label',
    // list<App\Gear\ForeverStat>
    'stats',
    // list<string> picked stat keys
    'picked',
    // array<string, int> result count by stat key
    'counts',
    // array<string, array{text: string, dot: string}> by stat key, for stats with a color
    'colors' => [],
])

<div class="flex flex-wrap items-center gap-1.5">
    <span class="w-24 shrink-0 text-xs tracking-wide text-gray-500 uppercase dark:text-gray-400">{{ $label }}</span>
    @foreach ($stats as $stat)
        @php($isActive = in_array($stat->key(), $picked, true))
        <x-filter.chip :active="$isActive" :count="$counts[$stat->key()]" :dot="$colors[$stat->key()]['dot'] ?? null" :class="! $isActive && $counts[$stat->key()] === 0 ? 'opacity-50' : null">
            <input type="checkbox" name="stats[]" value="{{ $stat->key() }}" class="sr-only" @checked($isActive)>
            {{ $stat->chipLabel() }}
        </x-filter.chip>
    @endforeach
</div>
