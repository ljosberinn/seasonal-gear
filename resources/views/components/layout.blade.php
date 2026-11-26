@php
    $railClass = 'row-span-full row-start-1 hidden border-x border-x-(--pattern-fg) bg-[image:repeating-linear-gradient(315deg,_var(--pattern-fg)_0,_var(--pattern-fg)_1px,_transparent_0,_transparent_50%)] bg-[size:10px_10px] bg-fixed 2xl:block';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" media="(prefers-color-scheme: light)" content="#fff">
        <meta name="theme-color" media="(prefers-color-scheme: dark)" content="#000">
        <link rel="icon" type="image/jpeg" href="{{ asset('logo.jpg') }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        {{-- inline so the stored theme applies before first paint; mirrors resources/js/theme.js --}}
        <script>
            (() => {
                let theme = 'system';
                try { theme = localStorage.getItem('theme') ?? 'system'; } catch {}
                const dark = theme === 'dark' || (theme === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', dark);
            })();
        </script>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Item names are already quality-colored server-side, so Wowhead only adds icons and tooltips. --}}
        {{-- matches Tailwind's sm breakpoint; Wowhead reads the size once, when it iconizes the links --}}
        <script>const whTooltips = { colorLinks: false, iconizeLinks: true, iconSize: matchMedia('(min-width: 40rem)').matches ? 'medium' : 'small', renameLinks: false };</script>
        <script src="https://wow.zamimg.com/js/tooltips.js" async></script>
    </head>
    <body class="bg-white font-sans text-black antialiased dark:bg-gray-950 dark:text-white">
        <div class="flex min-h-screen flex-col justify-between [--pattern-fg:var(--color-gray-950)]/5 dark:[--pattern-fg:var(--color-white)]/10">
            <x-site-header :build="$build ?? null" />
            <div class="grid flex-1 grid-cols-1 grid-rows-[1fr] md:grid-cols-[1fr_min(96rem,100%)_1fr]">
                <div aria-hidden="true" class="{{ $railClass }} col-start-1"></div>
                <main class="col-start-1 row-start-1 min-w-0 md:col-start-2">
                    {{ $slot }}
                </main>
                <div aria-hidden="true" class="{{ $railClass }} col-start-3"></div>
            </div>
            <x-site-footer />
        </div>
    </body>
</html>
