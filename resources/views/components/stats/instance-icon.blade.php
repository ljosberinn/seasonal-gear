@props(['icon' => null])

@if ($icon !== null)
    <img src="https://wow.zamimg.com/images/wow/icons/medium/{{ $icon }}.jpg" alt="" width="20" height="20" {{ $attributes->class('size-5 shrink-0 rounded-sm') }}>
@endif
