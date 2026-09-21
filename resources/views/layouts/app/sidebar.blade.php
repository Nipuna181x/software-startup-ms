@php
    $user = auth()->user();
    $groups = $user ? App\Support\Navigation::for($user) : collect();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>@include('partials.head')</head>
<body class="min-h-screen bg-[#fafbf9] text-zinc-950 antialiased" style="{{ $themeStyle ?? '' }}">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <flux:sidebar sticky collapsible="mobile" class="workspace-sidebar">
        <flux:sidebar.header class="!px-4 !pt-6 !pb-5">
            <a href="{{ route('dashboard') }}" wire:navigate aria-label="Startsuite dashboard"><x-wordmark /></a>
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>
        <div class="mx-3 flex min-w-0 items-center gap-3 rounded-xl border border-zinc-200/80 bg-zinc-50/70 p-3">
            @if ($organization?->logoUrl())
                <img src="{{ $organization->logoUrl() }}" alt="{{ $organization->name }}" class="size-9 shrink-0 rounded-lg object-cover" />
            @else
                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-brand-subtle text-xs font-semibold text-brand-active">{{ $organization?->initials() }}</span>
            @endif
            <div class="min-w-0"><p class="truncate text-xs font-medium">{{ $organization?->name }}</p><p class="mt-1 text-[10px] text-zinc-500">Your workspace</p></div>
        </div>
        <flux:sidebar.nav class="!px-3 !pt-7">
            <p class="mb-3 px-3 text-[10px] font-medium tracking-[0.15em] text-zinc-400 uppercase">Workspace</p>
            @foreach ($groups as $heading => $items)
                @foreach ($items as $item)
                    <a href="{{ route($item['route']) }}" wire:navigate class="workspace-nav" @if(request()->routeIs(explode('|', $item['pattern']))) aria-current="page" @endif>
                        <flux:icon :icon="$item['icon']" variant="outline" class="size-5 shrink-0" />{{ $item['label'] === 'Users' ? __('Team members') : __($item['label']) }}
                    </a>
                @endforeach
            @endforeach
            <p class="mt-8 mb-3 px-3 text-[10px] font-medium tracking-[0.15em] text-zinc-400 uppercase">On the roadmap</p>
            @foreach ([['squares-2x2', 'Smart Planner'], ['view-columns', 'Projects'], ['code-bracket', 'Code reviews'], ['bug-ant', 'Issues'], ['book-open', 'Knowledge base']] as [$icon, $label])
                <div class="flex items-center gap-3 px-3 py-2.5 text-[13px] text-zinc-400"><flux:icon :icon="$icon" class="size-4 shrink-0"/><span class="flex-1">{{ $label }}</span><span class="text-[9px]">Soon</span></div>
            @endforeach
        </flux:sidebar.nav>
        <flux:spacer />
        <div class="mx-3 mt-8 rounded-xl bg-[#f1f6eb] p-4"><span class="grid size-8 place-items-center rounded-full bg-white"><flux:icon icon="sparkles" class="size-4"/></span><p class="mt-3 text-sm font-medium">Good work starts here.</p><p class="mt-2 text-xs leading-5 text-zinc-500">Make this workspace feel like home.</p><a href="{{ route('profile.edit') }}" wire:navigate class="mt-4 inline-flex items-center gap-2 text-xs font-medium">Complete your profile <flux:icon icon="arrow-up-right" class="size-4 shrink-0" aria-hidden="true" /></a></div>
        <div class="mt-4 border-t border-zinc-100 px-3 pt-4 pb-3">
            <flux:dropdown position="top" align="start" class="w-full">
                <button class="flex w-full items-center gap-3 rounded-xl p-2 text-start hover:bg-zinc-50" aria-label="Open account menu">
                    <span class="grid size-9 shrink-0 place-items-center rounded-full bg-[#eee9e2] text-xs font-medium">{{ $user->initials() }}</span>
                    <span class="min-w-0 flex-1"><span class="block truncate text-xs font-medium">{{ $user->name }}</span><span class="mt-1 block text-[10px] text-zinc-500">{{ $user->role->label() }}</span></span><flux:icon icon="chevron-up-down" class="size-4 text-zinc-400"/>
                </button>
                <x-user-menu />
            </flux:dropdown>
        </div>
    </flux:sidebar>
    <flux:header class="border-b border-zinc-200 bg-white lg:hidden">
        <flux:sidebar.toggle icon="bars-2" inset="left" aria-label="Open navigation" />
        <span class="min-w-0 truncate text-sm font-medium">{{ $organization?->name }}</span>
        <flux:spacer />
        <flux:dropdown position="bottom" align="end"><flux:profile :initials="$user->initials()" icon-trailing="chevron-down"/><x-user-menu /></flux:dropdown>
    </flux:header>
    {{ $slot }}
    @persist('toast')<flux:toast.group><flux:toast /></flux:toast.group>@endpersist
    @fluxScripts
</body>
</html>
