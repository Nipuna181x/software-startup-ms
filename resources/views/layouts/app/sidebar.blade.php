@php
    use App\Support\Navigation;

    $user = auth()->user();
    $groups = $user ? Navigation::for($user) : collect();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-black antialiased" style="{{ $themeStyle ?? '' }}">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-black/6 bg-black/[0.02]">
            <flux:sidebar.header class="!px-4 !pt-5">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-3">
                    @if ($organization?->logoUrl())
                        <img
                            src="{{ $organization->logoUrl() }}"
                            alt="{{ $organization->name }}"
                            class="size-9 shrink-0 rounded-xl object-cover"
                        />
                    @else
                        <span
                            class="grid size-9 shrink-0 place-items-center rounded-xl text-[13px] font-semibold"
                            style="background:var(--brand);color:var(--brand-foreground)"
                        >{{ $organization?->initials() }}</span>
                    @endif

                    <span class="flex min-w-0 flex-col">
                        <span class="truncate text-[15px] font-medium tracking-tight">
                            {{ $organization?->name }}
                        </span>
                        <x-wordmark size="xs" tone="muted" class="!text-[10px]" />
                    </span>
                </a>

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav class="!px-2.5 !pt-4">
                @foreach ($groups as $heading => $items)
                    @foreach ($items as $item)
                        @php($isCurrent = request()->routeIs(explode('|', $item['pattern'])))
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            @class([
                                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors',
                                'font-medium' => $isCurrent,
                                'text-black/60 hover:bg-black/4 hover:text-black' => ! $isCurrent,
                            ])
                            @if ($isCurrent)
                                style="background:var(--brand-subtle);color:var(--brand-active)"
                            @endif
                        >
                            <flux:icon :icon="$item['icon']" variant="micro" class="shrink-0" />
                            {{ __($item['label']) }}
                        </a>
                    @endforeach
                @endforeach
            </flux:sidebar.nav>

            <flux:spacer />

            <div class="px-2.5 pb-4 max-lg:hidden">
                <flux:dropdown position="top" align="start" class="w-full">
                    <button class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start transition-colors hover:bg-black/4">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-black/6 text-[11px] font-semibold">
                            {{ auth()->user()->initials() }}
                        </span>
                        <span class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate text-sm font-medium">{{ auth()->user()->name }}</span>
                            <span class="truncate text-xs text-black/45">{{ auth()->user()->role->label() }}</span>
                        </span>
                        <flux:icon icon="chevrons-up-down" variant="micro" class="shrink-0 text-black/35" />
                    </button>

                    <x-user-menu />
                </flux:dropdown>
            </div>
        </flux:sidebar>

        {{-- Mobile header --}}
        <flux:header class="border-b border-black/6 lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

                <x-user-menu />
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
