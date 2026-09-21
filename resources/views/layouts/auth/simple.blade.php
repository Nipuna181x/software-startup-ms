<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>@include('partials.head')</head>
<body class="auth-surface min-h-screen bg-[#f0f0ed] text-zinc-950 antialiased">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <div class="mx-auto flex min-h-svh max-w-[1600px] flex-col p-4 sm:p-7">
        <header class="flex items-center justify-between gap-4 px-2 py-4 sm:px-5"><a href="{{ route('home') }}" wire:navigate aria-label="Startsuite home"><x-wordmark /></a><a href="{{ route('home') }}" wire:navigate class="text-xs text-zinc-600 hover:text-zinc-950">Back to website <flux:icon icon="arrow-up-right" class="inline-block size-4 shrink-0 align-text-bottom" aria-hidden="true" /></a></header>
        <main id="main-content" class="my-auto py-8">
            @if ($wide ?? false)
                <div class="mx-auto max-w-5xl rounded-3xl border border-zinc-200 bg-white p-6 sm:p-12">{{ $slot }}</div>
            @else
                <div class="mx-auto grid max-w-5xl overflow-hidden rounded-3xl border border-zinc-200 bg-white lg:grid-cols-2">
                    <div class="relative hidden flex-col justify-between overflow-hidden bg-[#eaf3df] p-12 lg:flex"><div><p class="eyebrow">A fresh perspective on teamwork</p><h2 class="display mt-8 text-[50px]">Great things<br>start with<br>your people.</h2><p class="mt-6 max-w-xs text-sm leading-7 text-zinc-600">A shared space for the team, the ideas, and everything you’ll build together.</p></div><div class="dot-grid mt-16 rounded-xl py-8"><div class="-rotate-3 rounded-xl border border-white bg-white/90 p-6 shadow-sm"><div class="flex items-center gap-3"><span class="grid size-10 place-items-center rounded-full bg-lime"><flux:icon icon="check" class="size-5"/></span><p class="text-sm font-medium">A little more connected.</p></div><p class="mt-4 text-xs text-zinc-500">Your next chapter starts here.</p></div></div><p class="mt-12 text-xs text-zinc-500">One team. One workspace. Endless possibilities.</p></div>
                    <div class="flex items-center p-6 sm:p-12"><div class="w-full">{{ $slot }}</div></div>
                </div>
            @endif
        </main>
        <footer class="flex flex-wrap justify-between gap-3 px-2 py-5 text-[11px] text-zinc-500 sm:px-5"><span>© {{ date('Y') }} Startsuite</span><span>Made for teams that make things.</span></footer>
    </div>
    @persist('toast')<flux:toast.group><flux:toast /></flux:toast.group>@endpersist
    @fluxScripts
</body>
</html>
