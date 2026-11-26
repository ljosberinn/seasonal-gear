@props([
    'legend',
    // query parameter suffix; the inputs submit as min_{name} and max_{name}
    'name',
    'min',
    'max',
    // picked bounds; null is the slider's end stop
    'low' => null,
    'high' => null,
])

@php($id = str_replace('_', '-', $name))

<fieldset @class([
    'flex flex-wrap items-center gap-2 text-sm',
    'font-bold' => $low !== null || $high !== null,
])>
    <legend class="float-left mr-1 text-gray-600 dark:text-gray-400">{{ $legend }}</legend>
    <label class="sr-only" for="min-{{ $id }}">Minimum {{ $legend }}</label>
    <input id="min-{{ $id }}" type="range" name="min_{{ $name }}" min="{{ $min }}" max="{{ $max }}" value="{{ $low ?? $min }}" data-output="min-{{ $id }}-value" class="w-32 accent-teal-600">
    <output id="min-{{ $id }}-value" for="min-{{ $id }}" class="w-6 text-right tabular-nums">{{ $low ?? $min }}</output>
    <span aria-hidden="true">–</span>
    <label class="sr-only" for="max-{{ $id }}">Maximum {{ $legend }}</label>
    <output id="max-{{ $id }}-value" for="max-{{ $id }}" class="w-6 tabular-nums">{{ $high ?? $max }}</output>
    <input id="max-{{ $id }}" type="range" name="max_{{ $name }}" min="{{ $min }}" max="{{ $max }}" value="{{ $high ?? $max }}" data-output="max-{{ $id }}-value" class="w-32 accent-teal-600">
</fieldset>
