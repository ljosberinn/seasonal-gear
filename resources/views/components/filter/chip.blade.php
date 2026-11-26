@props([
    'active' => false,
    'count' => null,
    'href' => null,
    'dot' => null,
    'icon' => null,
    'activeColor' => 'border-teal-500 bg-teal-500/10 ring-teal-500 dark:bg-teal-950/60',
    // any CSS color; tints text, border and background instead of the gray/`activeColor` scheme
    'color' => null,
])

@php
    $tag = $href === null ? 'label' : 'a';
    $inactive = 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/40';
@endphp

<{{ $tag }} @if ($href !== null) href="{{ $href }}" @if ($active) aria-current="true" @endif @endif @if ($color !== null) style="--chip-color: {{ $color }}" @endif {{ $attributes->class([
    'group inline-flex cursor-pointer items-center gap-1.5 rounded-md border py-0.5 pr-3 text-sm focus-visible:outline-2 focus-visible:outline-teal-500 has-focus-visible:outline-2 has-focus-visible:outline-teal-500',
    $icon === null ? 'pl-3' : 'pl-1',
    'font-bold ring-1 ring-inset' => $active,
    'text-gray-950 dark:text-white '.$activeColor => $active && $color === null,
    $inactive.' text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-100' => ! $active && $color === null,
    // class colors range from white to deep red, so light mode darkens them for contrast
    'text-[color-mix(in_oklab,var(--chip-color)_55%,black)] dark:text-(--chip-color)' => $color !== null,
    'border-(--chip-color) bg-(--chip-color)/15 ring-(--chip-color)' => $active && $color !== null,
    $inactive.' hover:border-(--chip-color)' => ! $active && $color !== null,
]) }}>
    @if ($icon)
        <img src="https://wow.zamimg.com/images/wow/icons/medium/{{ $icon }}.jpg" alt="" width="20" height="20" loading="lazy" @class([
            'size-5 rounded-sm transition',
            'opacity-75 grayscale-60 group-hover:opacity-100 group-hover:grayscale-0' => ! $active,
        ])>
    @endif
    @if ($dot)
        <span class="size-2 rounded-full {{ $dot }}"></span>
    @endif
    {{ $slot }}
    @if ($count !== null)
        <span class="font-normal text-gray-500 tabular-nums dark:text-gray-400">{{ $count }}</span>
    @endif
</{{ $tag }}>
