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
    <body class="min-h-screen bg-white" style="{{ $themeStyle ?? '' }}">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-2.5 py-1">
                    @if ($organization?->logoUrl())
                        <img
                            src="{{ $organization->logoUrl() }}"
                            alt="{{ $organization->name }}"
                            class="size-8 shrink-0 rounded-md object-cover"
                        />
                    @else
                        <span
                            class="grid size-8 shrink-0 place-items-center rounded-md text-xs font-semibold"
                            style="background:var(--brand);color:var(--brand-foreground)"
                        >{{ $organization?->initials() }}</span>
                    @endif

                    <span class="flex min-w-0 flex-col">
                        <span class="truncate text-sm font-semibold tracking-tight text-zinc-900">
                            {{ $organization?->name }}
                        </span>
                        <x-wordmark size="xs" tone="muted" class="!text-[10px] opacity-70" />
                    </span>
                </a>

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                @foreach ($groups as $heading => $items)
                    <flux:sidebar.group :heading="__($heading)" class="grid">
                        @foreach ($items as $item)
                            <flux:sidebar.item
                                :icon="$item['icon']"
                                :href="route($item['route'])"
                                :current="request()->routeIs(explode('|', $item['pattern']))"
                                wire:navigate
                            >
                                {{ __($item['label']) }}
                            </flux:sidebar.item>
                        @endforeach
                    </flux:sidebar.group>
                @endforeach
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <x-user-menu />
            </flux:dropdown>
        </flux:sidebar>

        {{-- Mobile header --}}
        <flux:header class="lg:hidden">
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
