@props([
    'active' => false,
    'count' => null,
    'href' => null,
    'icon' => null,
    // any CSS color; tints text, border and background instead of the gray/teal scheme
    'color' => null,
])

@php
    $tag = $href === null ? 'label' : 'a';
    $inactive = 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/40';
@endphp

<{{ $tag }} @if ($href !== null) href="{{ $href }}" @if ($active) aria-current="true" @endif @endif @if ($color !== null) style="--card-color: {{ $color }}" @endif {{ $attributes->class([
    'group flex cursor-pointer items-center gap-2 rounded-md border px-2.5 py-1.5 text-left text-sm leading-tight focus-visible:outline-2 focus-visible:outline-teal-500 has-focus-visible:outline-2 has-focus-visible:outline-teal-500',
    'font-bold ring-1 ring-inset' => $active,
    'border-teal-500 bg-teal-500/10 text-gray-950 ring-teal-500 dark:bg-teal-950/60 dark:text-white' => $active && $color === null,
    $inactive.' text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-100' => ! $active && $color === null,
    // class colors range from white to deep red, so light mode darkens them for contrast
    'text-[color-mix(in_oklab,var(--card-color)_55%,black)] dark:text-(--card-color)' => $color !== null,
    'border-(--card-color) bg-(--card-color)/15 ring-(--card-color)' => $active && $color !== null,
    $inactive.' hover:border-(--card-color)' => ! $active && $color !== null,
]) }}>
    @if ($icon)
        <img src="https://wow.zamimg.com/images/wow/icons/medium/{{ $icon }}.jpg" alt="" width="24" height="24" @class([
            'size-6 shrink-0 rounded-sm transition',
            'opacity-75 grayscale-60 group-hover:opacity-100 group-hover:grayscale-0' => ! $active,
        ])>
    @endif
    <span class="grow">{{ $slot }}</span>
    @if ($count !== null)
        <span class="font-normal text-gray-500 tabular-nums dark:text-gray-400">{{ $count }}</span>
    @endif
</{{ $tag }}>
