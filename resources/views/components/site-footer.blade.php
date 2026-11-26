@php
    $socialLinks = [
        'mail' => 'mailto:admin@gerritalex.de',
        'github' => 'https://github.com/ljosberinn',
        'youtube' => 'https://youtube.com/@xepher1s',
        'bluesky' => 'https://bsky.app/profile/xepheris.bsky.social',
    ];
@endphp

<footer class="border-t border-t-(--pattern-fg)">
    <div class="mx-auto flex h-24 w-full max-w-4xl flex-col items-center justify-between px-4 py-2 md:h-auto md:flex-row xl:max-w-7xl">
        <a href="https://github.com/ljosberinn/seasonal-gear" class="text-gray-500 underline underline-offset-4 hover:text-primary-500 dark:text-gray-400 dark:hover:text-primary-500">View Source</a>

        <div class="flex gap-4">
            @foreach ($socialLinks as $kind => $href)
                <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" class="group text-sm text-gray-500 transition">
                    <span class="sr-only">{{ $kind }}</span>
                    <x-dynamic-component :component="'icon.'.$kind" class="size-6 fill-current text-gray-700 hover:text-primary-500 dark:text-gray-200 dark:hover:text-primary-400" />
                </a>
            @endforeach
        </div>

        <div class="flex gap-2 text-gray-500 dark:text-gray-400">
            <span class="whitespace-nowrap">Gerrit Alex</span>
            <span>•</span>
            <span class="whitespace-nowrap">© {{ now()->year }}</span>
        </div>
    </div>
</footer>
