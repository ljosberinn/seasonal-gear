@props(['icon' => null, 'iconFileId' => null])

@if ($iconFileId !== null)
    {{-- zamimg serves some recent icons only under their file ID, not their listfile name --}}
    <img src="https://wow.zamimg.com/images/wow/icons/medium/{{ $icon ?? $iconFileId }}.jpg" @if ($icon !== null) onerror="this.onerror = null; this.src = 'https://wow.zamimg.com/images/wow/icons/medium/{{ $iconFileId }}.jpg'" @endif alt="" width="20" height="20" {{ $attributes->class('size-5 shrink-0 rounded-sm') }}>
@endif
