@props([
    'active' => false,
    'count' => null,
    'href' => null,
])

@php
    $tag = $href === null ? 'label' : 'a';
@endphp

<{{ $tag }} @if ($href !== null) href="{{ $href }}" @if ($active) aria-current="true" @endif @endif {{ $attributes->class([
    'flex cursor-pointer items-center justify-between gap-2 rounded-md border px-2.5 py-1.5 text-left text-sm leading-tight focus-visible:outline-2 focus-visible:outline-teal-500 has-focus-visible:outline-2 has-focus-visible:outline-teal-500',
    'border-teal-500 bg-teal-500/10 font-bold text-gray-950 ring-1 ring-teal-500 ring-inset dark:bg-teal-950/60 dark:text-white' => $active,
    'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-100' => ! $active,
]) }}>
    <span>{{ $slot }}</span>
    @if ($count !== null)
        <span class="font-normal text-gray-500 tabular-nums dark:text-gray-400">{{ $count }}</span>
    @endif
</{{ $tag }}>
