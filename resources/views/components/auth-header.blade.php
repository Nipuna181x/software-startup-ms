@props([
    'title',
    'description' => null,
])

<div class="flex w-full flex-col">
    <h1 class="display text-[32px]">{{ $title }}</h1>

    @if ($description)
        <p class="mt-3 text-sm leading-relaxed text-black/55">{{ $description }}</p>
    @endif
</div>
