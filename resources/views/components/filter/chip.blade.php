@props([
    'active' => false,
    'count' => null,
    'href' => null,
    'dot' => null,
    'activeColor' => 'border-teal-500 bg-teal-500/10 ring-teal-500 dark:bg-teal-950/60',
])

@php
    $tag = $href === null ? 'label' : 'a';
@endphp

<{{ $tag }} @if ($href !== null) href="{{ $href }}" @if ($active) aria-current="true" @endif @endif {{ $attributes->class([
    'inline-flex cursor-pointer items-center gap-1.5 rounded-md border px-3 py-0.5 text-sm focus-visible:outline-2 focus-visible:outline-teal-500 has-focus-visible:outline-2 has-focus-visible:outline-teal-500',
    'font-bold text-gray-950 ring-1 ring-inset dark:text-white '.$activeColor => $active,
    'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-100' => ! $active,
]) }}>
    @if ($dot)
        <span class="size-2 rounded-full {{ $dot }}"></span>
    @endif
    {{ $slot }}
    @if ($count !== null)
        <span class="font-normal text-gray-500 tabular-nums dark:text-gray-400">{{ $count }}</span>
    @endif
</{{ $tag }}>
