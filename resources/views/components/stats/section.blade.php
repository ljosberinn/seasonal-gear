@props(['title'])

<section {{ $attributes->class('mt-10') }}>
    <h2 class="text-lg font-semibold">{{ $title }}</h2>
    @isset($description)
        <p class="mt-1 max-w-3xl text-sm text-gray-600 dark:text-gray-400">{{ $description }}</p>
    @endisset
    <div class="mt-4">{{ $slot }}</div>
</section>
