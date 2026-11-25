<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name') }}</title>

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{-- Item names are already quality-colored server-side, so Wowhead only adds icons and tooltips. --}}
        <script>const whTooltips = { colorLinks: false, iconizeLinks: true, iconSize: 'small', renameLinks: false };</script>
        <script src="https://wow.zamimg.com/js/tooltips.js" async></script>
    </head>
    <body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        {{ $slot }}
    </body>
</html>
