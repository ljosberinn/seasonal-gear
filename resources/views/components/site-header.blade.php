@props(['build' => null])

@php
    use Illuminate\Support\Carbon;

    // assets are built during every deploy, so the manifest dates the running site
    $manifest = public_path('build/manifest.json');
    $builtAt = is_file($manifest) ? Carbon::createFromTimestamp(filemtime($manifest)) : null;
    $themes = [
        'light' => ['label' => 'Light', 'icon' => 'icon.sun'],
        'dark' => ['label' => 'Dark', 'icon' => 'icon.moon'],
        'system' => ['label' => 'System', 'icon' => 'icon.monitor'],
    ];
@endphp

<header class="border-b border-b-(--pattern-fg) bg-white dark:bg-gray-950">
    <div class="mx-auto flex w-full max-w-4xl items-center justify-between px-4 py-10 xl:max-w-7xl">
        <a href="{{ route('gear.index') }}" class="flex items-center">
            <img src="{{ asset('logo.jpg') }}" width="32" height="32" class="mr-3 size-8 rounded-sm" alt="">
            <span class="text-2xl font-semibold">Seasonal Gear</span>
        </a>

        <div class="flex items-center gap-4 sm:gap-6">
            <nav aria-label="Main" class="flex gap-3 text-sm sm:gap-4">
                @foreach (['gear.*' => ['Gear', route('gear.index')], 'statistics.*' => ['Statistics', route('statistics.index')]] as $pattern => [$label, $href])
                    <a href="{{ $href }}" @if (request()->routeIs($pattern)) aria-current="page" @endif class="hover:text-primary-500 aria-[current=page]:font-semibold dark:hover:text-primary-400">{{ $label }}</a>
                @endforeach
            </nav>

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
