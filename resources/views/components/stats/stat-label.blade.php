@props([
    // a secondary or a pair key such as `crit-haste`
    'stat',
    'long' => false,
])

@php
    // same identity colors as the stat filter chips on the gear page
    $stats = [
        'crit' => ['label' => 'Crit', 'long' => 'Critical Strike', 'dot' => 'bg-red-500'],
        'haste' => ['label' => 'Haste', 'long' => 'Haste', 'dot' => 'bg-amber-500'],
        'mastery' => ['label' => 'Mastery', 'long' => 'Mastery', 'dot' => 'bg-purple-500'],
        'vers' => ['label' => 'Vers', 'long' => 'Versatility', 'dot' => 'bg-green-500'],
    ];
@endphp

<span {{ $attributes->class('inline-flex items-center gap-1.5 whitespace-nowrap') }}>
    @foreach (explode('-', $stat) as $index => $key)
        @if ($index > 0)
            <span class="text-gray-400 dark:text-gray-500">/</span>
        @endif
        <span class="inline-flex items-center gap-1"><span class="size-2 rounded-full {{ $stats[$key]['dot'] }}"></span>{{ $stats[$key][$long ? 'long' : 'label'] }}</span>
    @endforeach
</span>
