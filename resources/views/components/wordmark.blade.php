@props([
    'size' => 'base',
    'tone' => 'ink',
])

@php
    $sizes = [
        'xs' => 'text-xs',
        'sm' => 'text-base',
        'base' => 'text-xl',
        'lg' => 'text-2xl',
        'xl' => 'text-4xl',
    ];

    $tones = [
        'ink' => 'text-black',
        'muted' => 'text-black/45',
        'inverse' => 'text-white',
    ];

    $accent = match ($tone) {
        'muted' => 'bg-black/35',
        'inverse' => 'bg-white',
        default => 'bg-black',
    };

    $square = match ($size) {
        'xs' => 'size-[3px] mb-[2px]',
        'sm' => 'size-[4px] mb-[3px]',
        'lg' => 'size-[6px] mb-[4px]',
        'xl' => 'size-[9px] mb-[6px]',
        default => 'size-[5px] mb-[4px]',
    };
@endphp

<span {{ $attributes->class(['wordmark inline-flex items-end', $sizes[$size] ?? $sizes['base'], $tones[$tone] ?? $tones['ink']]) }}>
    <span class="wordmark-strong">Start</span><span class="wordmark-light">suite</span><span
        aria-hidden="true"
        class="{{ $accent }} {{ $square }} ml-[0.16em] shrink-0 rounded-[1px]"
    ></span>
</span>
