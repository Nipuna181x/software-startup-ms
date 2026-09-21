@props([
    'size' => 'base',
    'tone' => 'ink',
])

@php
    $sizes = [
        'xs' => 'text-sm',
        'sm' => 'text-base',
        'base' => 'text-xl',
        'lg' => 'text-2xl',
        'xl' => 'text-4xl',
    ];

    $tones = [
        'ink' => 'text-[color:var(--color-ink)]',
        'muted' => 'text-zinc-500',
        'inverse' => 'text-white',
        'brand' => 'text-[color:var(--brand,var(--color-ink))]',
    ];

    $accent = match ($tone) {
        'muted' => 'bg-zinc-400',
        'inverse' => 'bg-white',
        default => 'bg-[color:var(--color-ink)]',
    };

    $square = match ($size) {
        'xs' => 'size-[3px] mb-[2px]',
        'sm' => 'size-[4px] mb-[2px]',
        'lg' => 'size-[6px] mb-[3px]',
        'xl' => 'size-[9px] mb-[5px]',
        default => 'size-[5px] mb-[3px]',
    };
@endphp

<span {{ $attributes->class(['wordmark inline-flex items-end', $sizes[$size] ?? $sizes['base'], $tones[$tone] ?? $tones['ink']]) }}>
    <span class="wordmark-strong">Start</span><span class="wordmark-light">suite</span><span
        aria-hidden="true"
        class="{{ $accent }} {{ $square }} ml-[0.14em] shrink-0"
    ></span>
</span>
