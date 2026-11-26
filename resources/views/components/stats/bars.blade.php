@props([
    // list of array{stat: string, count: int, share: float, reference?: float|null}
    'rows',
    // what the reference tick stands for, e.g. "even split"
    'referenceLabel' => null,
])

@php
    $scale = max(0.01, ...array_map(fn (array $row): float => max($row['share'], $row['reference'] ?? 0), $rows));
@endphp

<div {{ $attributes->class('max-w-2xl space-y-2') }}>
    @foreach ($rows as $row)
        @php($title = round($row['share'] * 100, 1).'% ('.$row['count'].' items)'.(isset($row['reference']) ? ' · '.$referenceLabel.': '.round($row['reference'] * 100, 1).'%' : ''))
        <div class="grid grid-cols-[8.5rem_1fr_4.5rem] items-center gap-3 text-sm" title="{{ $title }}">
            <x-stats.stat-label :stat="$row['stat']" />
            <div class="relative h-3">
                <div class="absolute inset-y-0 left-0 rounded-r bg-(--viz-series)" style="width: {{ $row['share'] / $scale * 100 }}%"></div>
                @isset($row['reference'])
                    <div class="absolute -inset-y-1 w-0.5 bg-gray-950 dark:bg-white" style="left: {{ $row['reference'] / $scale * 100 }}%"></div>
                @endisset
            </div>
            <span class="text-right tabular-nums">{{ round($row['share'] * 100) }}%</span>
        </div>
    @endforeach
    @if ($referenceLabel)
        <p class="flex items-center gap-2 pt-1 text-xs text-gray-500 dark:text-gray-400"><span class="inline-block h-3 w-0.5 bg-gray-950 dark:bg-white"></span>{{ $referenceLabel }}</p>
    @endif
</div>
