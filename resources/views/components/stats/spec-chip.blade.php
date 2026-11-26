@props(['spec'])

<a href="{{ route('statistics.spec', ['class' => $spec['classKey'], 'spec' => $spec['specKey']]) }}" {{ $attributes->class('inline-flex items-center gap-1.5 align-middle font-medium hover:underline') }}>
    <img src="https://wow.zamimg.com/images/wow/icons/medium/{{ $spec['icon'] }}.jpg" alt="" width="20" height="20" class="size-5 rounded-sm">
    {{ $spec['specName'] }} {{ $spec['className'] }}
</a>
