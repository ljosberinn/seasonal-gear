@props(['instance'])

<a href="{{ route('statistics.instance', $instance['id']) }}" {{ $attributes->class('inline-flex items-center gap-1.5 align-middle font-medium hover:underline') }}>
    <x-stats.instance-icon :icon="$instance['icon']" :icon-file-id="$instance['iconFileId']" />
    {{ $instance['name'] }}
</a>
