@props([
    'legend',
    // query parameter suffix; the inputs submit as min_{name} and max_{name}
    'name',
    'min',
    // null leaves the range open-ended
    'max' => null,
    // picked bounds; null is no bound
    'low' => null,
    'high' => null,
])

@php($id = str_replace('_', '-', $name))

<fieldset @class([
    'flex flex-wrap items-center gap-2 text-sm',
    'font-bold' => $low !== null || $high !== null,
])>
    <legend class="float-left mr-1 text-gray-600 dark:text-gray-400">{{ $legend }}</legend>
    @foreach (['min' => $low, 'max' => $high] as $bound => $value)
        @if ($bound === 'max')
            <span aria-hidden="true">–</span>
        @endif
        <label class="sr-only" for="{{ $bound }}-{{ $id }}">{{ $bound === 'min' ? 'Minimum' : 'Maximum' }} {{ $legend }}</label>
        <input id="{{ $bound }}-{{ $id }}" type="number" inputmode="numeric" name="{{ $bound }}_{{ $name }}" min="{{ $min }}" @if ($max !== null) max="{{ $max }}" @endif value="{{ $value }}" placeholder="{{ $bound === 'min' ? $min : ($max ?? 'Any') }}" class="w-16 rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 tabular-nums placeholder:font-normal placeholder:text-gray-400 focus-visible:outline-2 focus-visible:outline-teal-500 dark:border-gray-800 dark:bg-gray-900/40 dark:placeholder:text-gray-500">
    @endforeach
</fieldset>
