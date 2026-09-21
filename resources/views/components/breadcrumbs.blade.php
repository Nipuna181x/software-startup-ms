@props(['trail' => []])

{{--
    $trail is an ordered list of ['label' => string, 'route' => string|null].
    The last item (or any item with a null route) renders as plain text.
--}}
<nav aria-label="{{ __('Breadcrumb') }}" class="flex items-center gap-1.5 text-sm text-black/45">
    @foreach ($trail as $index => $crumb)
        @if ($index > 0)
            <flux:icon icon="chevron-right" class="size-3.5 shrink-0 text-black/25" />
        @endif

        @if (! empty($crumb['route']) && ! $loop->last)
            <a href="{{ $crumb['route'] }}" wire:navigate class="transition-colors hover:text-black">
                {{ $crumb['label'] }}
            </a>
        @else
            <span class="{{ $loop->last ? 'font-medium text-black' : '' }}">{{ $crumb['label'] }}</span>
        @endif
    @endforeach
</nav>
