@props([
    // list of array{href: string, label: string, active: bool}
    'options',
    'label',
    'icon' => null,
    'color' => null,
])

{{-- small-screen stand-in for a card grid of links; resources/js/app.js navigates on change --}}
<div @if ($color !== null) style="--card-color: {{ $color }}" @endif {{ $attributes->class('relative') }}>
    @if ($icon)
        <img src="https://wow.zamimg.com/images/wow/icons/medium/{{ $icon }}.jpg" alt="" width="32" height="32" class="pointer-events-none absolute top-1/2 left-2 size-8 -translate-y-1/2 rounded-sm">
    @endif
    <select data-navigate aria-label="{{ $label }}" @class([
        'w-full cursor-pointer appearance-none rounded-md border py-2 pr-10 text-base leading-tight font-bold ring-1 ring-inset focus-visible:outline-2 focus-visible:outline-teal-500',
        $icon === null ? 'pl-3' : 'pl-12',
        'border-teal-500 bg-teal-500/10 text-gray-950 ring-teal-500 dark:bg-teal-950/60 dark:text-white' => $color === null,
        'border-(--card-color) bg-(--card-color)/15 text-[color-mix(in_oklab,var(--card-color)_55%,black)] ring-(--card-color) dark:text-(--card-color)' => $color !== null,
    ])>
        @foreach ($options as $option)
            <option value="{{ $option['href'] }}" @selected($option['active'])>{{ $option['label'] }}</option>
        @endforeach
    </select>
    <x-icon.chevron-down class="pointer-events-none absolute top-1/2 right-3 size-5 -translate-y-1/2 text-gray-600 dark:text-gray-300" />
</div>
