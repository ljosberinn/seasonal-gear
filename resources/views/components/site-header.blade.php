@php
    $navLinks = [
        ['href' => 'https://gerritalex.de', 'title' => 'Home'],
        ['href' => 'https://gerritalex.de/blog', 'title' => 'Blog'],
        ['href' => 'https://gerritalex.de/music', 'title' => 'Music'],
        ['href' => 'https://gerritalex.de/series', 'title' => 'Series'],
        ['href' => 'https://gerritalex.de/movies', 'title' => 'Movies'],
        ['href' => 'https://gerritalex.de/about', 'title' => 'About'],
    ];
    $themes = [
        'light' => ['label' => 'Light', 'icon' => 'icon.sun'],
        'dark' => ['label' => 'Dark', 'icon' => 'icon.moon'],
        'system' => ['label' => 'System', 'icon' => 'icon.monitor'],
    ];
@endphp

<header class="border-b border-b-(--pattern-fg) bg-white dark:bg-gray-950">
    <div class="mx-auto flex w-full max-w-4xl items-center justify-between px-4 py-10 xl:max-w-7xl">
        <a href="https://gerritalex.de" aria-label="Xepheris" class="flex items-center">
            <img src="{{ asset('logo.png') }}" width="32" height="32" class="mr-3 size-8" alt="logo">
            <span class="hidden text-2xl font-semibold sm:block">Xepheris</span>
        </a>

        <div class="flex items-center gap-4 leading-5 sm:gap-6">
            <nav class="hidden items-center gap-4 sm:flex sm:gap-6">
                @foreach (array_slice($navLinks, 1) as $link)
                    <a href="{{ $link['href'] }}" @if ($link['title'] === 'About') rel="me" @endif class="block font-medium text-gray-900 hover:text-primary-500 dark:text-gray-100 dark:hover:text-primary-400">{{ $link['title'] }}</a>
                @endforeach
            </nav>

            <div data-theme-switch class="relative mr-5 flex items-center">
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

            <button type="button" data-mobile-nav-open aria-label="Toggle Menu" class="sm:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-8 text-gray-900 hover:text-primary-500 dark:text-gray-100 dark:hover:text-primary-400">
                    <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                </svg>
            </button>
            <dialog data-mobile-nav class="m-0 h-full max-h-none w-full max-w-none translate-x-0 bg-white opacity-95 transition-[translate,opacity,display,overlay] transition-discrete duration-300 ease-in-out not-open:translate-x-full not-open:opacity-0 not-open:duration-200 not-open:ease-in backdrop:bg-black/25 dark:bg-gray-950 dark:opacity-[0.98] starting:open:translate-x-full starting:open:opacity-0">
                <nav class="mt-8 flex h-full basis-0 flex-col items-start overflow-y-auto pt-2 pl-12 text-left">
                    @foreach ($navLinks as $link)
                        <a href="{{ $link['href'] }}" class="mb-4 py-2 pr-4 text-2xl font-bold tracking-widest text-gray-900 hover:text-primary-500 dark:text-gray-100 dark:hover:text-primary-400">{{ $link['title'] }}</a>
                    @endforeach
                </nav>
                <button type="button" data-mobile-nav-close aria-label="Toggle Menu" class="fixed top-7 right-4 size-16 p-4 text-gray-900 hover:text-primary-500 dark:text-gray-100 dark:hover:text-primary-400">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </dialog>
        </div>
    </div>
</header>
