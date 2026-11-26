@props(['classKey', 'specKey', 'playerClass'])

@php
    $specName = $playerClass['specs'][$specKey]['name'].' '.$playerClass['name'];
    // rpglogs numbers spec art by in-game spec order, which config/classes.php follows
    $specArt = 'https://assets.rpglogs.com/img/warcraft/builds/specs/'.str_replace('-', '', $classKey).'-'.(array_search($specKey, array_keys($playerClass['specs']), true) + 1).'.jpg';
@endphp

<a href="https://www.archon.gg/wow/builds/{{ $specKey }}/{{ $classKey }}/mythic-plus/overview/10/all-dungeons/this-week" target="_blank" rel="noopener" {{ $attributes->class('group relative flex items-center gap-4 overflow-hidden rounded-md border border-gray-800 bg-gray-950 px-4 py-3 text-white') }}>
    <span aria-hidden="true" class="absolute inset-y-0 left-0 w-40 bg-cover bg-center mask-r-from-30% sm:w-56" style="background-image: url('{{ $specArt }}')"></span>
    <span aria-hidden="true" class="absolute inset-y-0 right-0 hidden w-96 bg-cover bg-left opacity-40 mask-l-from-20% lg:block" style="background-image: url('https://assets.rpglogs.com/img/warcraft/builds/crosslink-gear.png')"></span>

    <span class="relative flex-1 pl-24 sm:pl-44">
        <span class="block text-sm font-bold tracking-wide uppercase"><span style="color: {{ $playerClass['color'] }}">{{ $specName }}</span> Meta Build</span>
        <span class="block text-sm text-gray-300">See what <span style="color: {{ $playerClass['color'] }}">{{ $specName }}</span> players currently use.</span>
        <span class="mt-0.5 inline-flex items-center gap-1 text-sm font-semibold text-sky-400 group-hover:underline">Open Archon <x-icon.external-link class="size-4" /></span>
    </span>

    <span class="relative hidden flex-col items-center md:flex">
        <img src="https://assets.rpglogs.com/img/archon/logo.svg" alt="" class="size-8">
        <span class="bg-linear-to-r from-[#c0f] to-[#5200ff] bg-clip-text font-[Avenir,Arial,sans-serif]! text-lg font-bold tracking-wider text-transparent uppercase italic">Archon</span>
    </span>

    <span class="relative hidden flex-1 text-right lg:block">
        <span class="block text-sm font-bold tracking-wide uppercase">Data-Driven Meta Builds</span>
        <span class="block text-sm text-gray-300">Discover the most popular builds for <span style="color: {{ $playerClass['color'] }}">{{ $specName }}</span>.</span>
    </span>
</a>
