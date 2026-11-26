@props([
    'active' => false,
])

<div class="relative">
    <select {{ $attributes->class([
        'cursor-pointer appearance-none rounded-md border py-0.5 pr-8 pl-3 text-sm focus-visible:outline-2 focus-visible:outline-teal-500',
        'border-teal-500 bg-teal-500/10 font-bold text-gray-950 ring-1 ring-teal-500 ring-inset dark:bg-teal-950/60 dark:text-white' => $active,
        'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-100' => ! $active,
    ]) }}>
        {{ $slot }}
    </select>
    <x-icon.chevron-down class="pointer-events-none absolute top-1/2 right-2 size-4 -translate-y-1/2 text-gray-500 dark:text-gray-400" />
</div>
