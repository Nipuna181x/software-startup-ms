@props([
    'title',
    'description' => null,
])

<div class="flex w-full flex-col">
    <h1 class="text-2xl font-semibold tracking-tight text-balance">{{ $title }}</h1>

    @if ($description)
        <p class="mt-2 text-sm leading-relaxed text-zinc-500">{{ $description }}</p>
    @endif
</div>
