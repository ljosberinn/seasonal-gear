@props(['label', 'value'])

<div {{ $attributes->class('rounded-md border border-gray-200 px-3 py-2 dark:border-gray-800') }}>
    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
    <div class="text-xl font-semibold">{{ $value }}</div>
</div>
