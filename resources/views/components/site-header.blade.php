@props(['build' => null])

@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\File;

    // assets are built during every deploy, so the manifest dates the running site
    $manifest = public_path('build/manifest.json');
    $builtAt = File::exists($manifest) ? Carbon::createFromTimestamp(File::lastModified($manifest)) : null;
    $gameVersions = [
        // statistics are retail-only, so Retail stays marked there without being the current page
        ['label' => 'Retail', 'href' => route('gear.index'), 'icon' => 'retail.webp', 'current' => request()->routeIs('gear.*') ? 'page' : (request()->routeIs('statistics.*') ? 'true' : null)],
        ['label' => 'Forever', 'href' => route('forever.index'), 'icon' => 'forever.png', 'current' => request()->routeIs('forever.*') ? 'page' : null],
    ];
    $themes = [
        'light' => ['label' => 'Light', 'icon' => 'icon.sun'],
        'dark' => ['label' => 'Dark', 'icon' => 'icon.moon'],
        'system' => ['label' => 'System', 'icon' => 'icon.monitor'],
    ];
@endphp

<header class="border-b border-b-(--pattern-fg) bg-white dark:bg-gray-950">
    <div class="mx-auto flex w-full max-w-4xl flex-wrap items-center justify-between gap-y-4 px-4 py-10 xl:max-w-7xl">
        <div class="flex items-center gap-4 sm:gap-6">
            <a href="{{ route('gear.index') }}" class="flex items-center">
                <img src="{{ asset('logo.jpg') }}" width="32" height="32" class="mr-3 size-8 rounded-sm" alt="">
                <span class="text-2xl font-semibold">Seasonal Gear</span>
            </a>

            <nav aria-label="Game Version" class="flex gap-1.5">
                @foreach ($gameVersions as $version)
                    <a href="{{ $version['href'] }}" @if ($version['current']) aria-current="{{ $version['current'] }}" @endif @class([
                        'flex items-center gap-2 rounded-md border px-2 py-1 text-sm focus-visible:outline-2 focus-visible:outline-teal-500',
                        'border-teal-500 bg-teal-500/10 font-bold ring-1 ring-teal-500 ring-inset dark:bg-teal-950/60' => $version['current'] !== null,
                        'border-gray-200 bg-gray-50 text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-100' => $version['current'] === null,
                    ])>
                        <img src="{{ asset('images/game-versions/'.$version['icon']) }}" alt="" width="24" height="24" class="size-6 shrink-0 object-contain">
                        {{-- phones show only the logos, next to the site name --}}
                        <span class="sr-only sm:not-sr-only">{{ $version['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="flex items-center gap-4 sm:gap-6">
            {{-- Forever's DB2 has no Encounter Journal or specs, which the statistics are built on --}}
            @if (request()->routeIs('gear.*', 'statistics.*'))
                <nav aria-label="Main" class="flex gap-3 text-sm sm:gap-4">
                    <a href="{{ route('statistics.index') }}" @if (request()->routeIs('statistics.*')) aria-current="page" @endif class="hover:text-primary-500 aria-[current=page]:font-semibold dark:hover:text-primary-400">Statistics</a>
                </nav>
            @endif

            <div class="hidden flex-col items-end sm:flex text-sm text-gray-500 dark:text-gray-400">
                @if ($build)
                    <span>Build {{ $build }}</span>
                @endif
                @if ($builtAt)
                    <time datetime="{{ $builtAt->toIso8601String() }}" data-relative-time="Built" title="{{ $builtAt->toDayDateTimeString() }} UTC">Built {{ $builtAt->diffForHumans() }}</time>
                @endif
            </div>

            <div data-theme-switch class="relative flex items-center">
                <button type="button" data-theme-button aria-label="Theme Switcher" aria-haspopup="menu" aria-expanded="false" aria-controls="theme-menu" class="flex cursor-pointer items-center justify-center hover:text-primary-500 dark:hover:text-primary-400">
                    <x-icon.sun class="size-6 dark:hidden" />
                    <x-icon.moon class="hidden size-6 dark:block" />
                </button>
                <div id="theme-menu" data-theme-menu role="menu" hidden class="absolute top-full right-0 z-50 mt-2 w-32 origin-top-right rounded-md bg-white p-1 shadow-lg ring-1 ring-black/5 dark:bg-gray-800">
                    @foreach ($themes as $value => $theme)
                        <button type="button" role="menuitemradio" aria-checked="false" data-theme-value="{{ $value }}" class="group flex w-full cursor-pointer items-center rounded-md px-2 py-2 text-sm hover:bg-primary-600 hover:text-white focus-visible:bg-primary-600 focus-visible:text-white focus-visible:outline-none aria-checked:font-semibold">
                            <x-dynamic-component :component="$theme['icon']" class="mr-2 size-6" />
                            {{ $theme['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</header>
