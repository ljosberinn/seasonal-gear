@props([
    'value',
    // 'scale': 0..1 magnitude, 'diverging': -1..1 below/above a reference, 'gap': nothing where something could be
    'tone' => 'scale',
    'intensity' => 0,
    'title' => null,
    'href' => null,
])

@php
    $strength = round(min(1, abs($intensity)) * 55);
    $color = match (true) {
        $tone === 'gap' => 'color-mix(in oklab, var(--viz-gap) 25%, transparent)',
        $tone === 'diverging' && $intensity < 0 => "color-mix(in oklab, var(--viz-below) {$strength}%, transparent)",
        $tone === 'diverging' || $tone === 'scale' => "color-mix(in oklab, var(--viz-above) {$strength}%, transparent)",
        default => null,
    };
@endphp

<td @if ($color) style="background-color: {{ $color }}" @endif @if ($title) title="{{ $title }}" @endif {{ $attributes->class(['px-2 py-1.5 text-center tabular-nums', 'font-bold' => $tone === 'gap']) }}>
    @if ($href)
        <a href="{{ $href }}" class="hover:underline">{{ $value }}</a>
    @else
        {{ $value }}
    @endif
</td>
