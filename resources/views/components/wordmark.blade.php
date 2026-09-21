@props(['size' => 'base', 'tone' => 'ink'])
@php
    $sizes = ['xs' => 'text-xs', 'sm' => 'text-base', 'base' => 'text-xl', 'lg' => 'text-2xl', 'xl' => 'text-4xl'];
    $tones = ['ink' => 'text-zinc-950', 'muted' => 'text-zinc-500', 'inverse' => 'text-white'];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-2.5 font-semibold tracking-[-0.055em]', $sizes[$size] ?? $sizes['base'], $tones[$tone] ?? $tones['ink']]) }}>
    <svg viewBox="0 0 36 36" fill="none" aria-hidden="true" class="h-[1.5em] w-[1.5em] shrink-0">
        <path d="M18 3v30M3 18h30M7.4 7.4l21.2 21.2M7.4 28.6L28.6 7.4" stroke="currentColor" stroke-width="5" stroke-linecap="round"/>
        <circle cx="18" cy="18" r="7" fill="currentColor"/>
        <circle cx="18" cy="18" r="3" fill="#b8f66b"/>
    </svg>
    <span>startsuite<span class="text-lime-600">.</span></span>
</span>
