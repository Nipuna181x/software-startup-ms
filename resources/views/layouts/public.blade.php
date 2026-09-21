<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>@include('partials.head')</head>
<body class="bg-[#f0f0ed] text-zinc-950 antialiased sm:p-4 lg:p-7">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <div class="mx-auto max-w-[1480px] overflow-clip bg-white sm:rounded-[24px]">
        <div class="m-2 rounded-lg bg-lime px-4 py-2.5 text-center text-xs font-medium sm:text-sm">Big ideas. Clear plans. Better teamwork. <a href="#features" class="ml-2 inline-flex items-center gap-1 underline underline-offset-4">Meet Startsuite <span aria-hidden="true">↗</span></a></div>
        <header x-data="{ open: false }" class="relative z-30 mx-auto max-w-7xl border-b border-zinc-100 px-5 sm:px-10">
            <div class="flex h-22 items-center justify-between gap-4">
                <a href="{{ route('home') }}" wire:navigate aria-label="Startsuite home"><x-wordmark /></a>
                <nav aria-label="Main navigation" class="hidden items-center gap-7 text-sm lg:flex">
                    <a href="{{ route('home') }}" class="rounded-lg bg-zinc-50 px-4 py-2">Home</a>
                    <a href="#features" class="hover:text-lime-700">Features</a>
                    <a href="#how-it-works" class="hover:text-lime-700">How it works</a>
                    <a href="#workspace" class="hover:text-lime-700">Your workspace</a>
                    <a href="#faq" class="hover:text-lime-700">FAQs</a>
                </nav>
                <div class="flex items-center gap-2 sm:gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" wire:navigate class="button-dark">Open workspace <span aria-hidden="true">↗</span></a>
                    @else
                        <a href="{{ route('login') }}" wire:navigate class="hidden text-sm font-medium sm:block">Log in</a>
                        <a href="{{ route('register') }}" wire:navigate class="button-outline !px-4">Get started <span aria-hidden="true">↗</span></a>
                    @endauth
                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="mobile-navigation" aria-label="Toggle navigation" class="grid size-10 place-items-center rounded-lg border border-zinc-200 lg:hidden"><flux:icon icon="bars-2" class="size-5" /></button>
                </div>
            </div>
            <nav id="mobile-navigation" x-cloak x-show="open" @click="open = false" @keydown.escape.window="open = false" aria-label="Mobile navigation" class="grid gap-1 border-t border-zinc-100 py-4 text-sm lg:hidden">
                <a href="#features" class="rounded-lg p-3 hover:bg-zinc-50">Features</a><a href="#how-it-works" class="rounded-lg p-3 hover:bg-zinc-50">How it works</a><a href="#workspace" class="rounded-lg p-3 hover:bg-zinc-50">Your workspace</a><a href="#faq" class="rounded-lg p-3 hover:bg-zinc-50">FAQs</a><a href="{{ route('login') }}" wire:navigate class="rounded-lg p-3 hover:bg-zinc-50">Log in</a>
            </nav>
        </header>
        <main id="main-content">{{ $slot }}</main>
        <footer class="mx-auto max-w-7xl px-5 pt-20 pb-7 sm:px-10">
            <div class="grid gap-10 border-b border-zinc-200 pb-12 md:grid-cols-[1.8fr_1fr_1fr]">
                <div><a href="{{ route('home') }}" aria-label="Startsuite home"><x-wordmark /></a><p class="mt-5 max-w-xs text-sm leading-7 text-zinc-500">A little more clarity.<br>A lot more progress.<br>One workspace for your software team.</p></div>
                <div><h2 class="mb-5 text-sm font-semibold">Explore the product</h2><div class="grid gap-3 text-sm text-zinc-600"><a href="#features">Features</a><a href="#how-it-works">How it works</a><a href="#workspace">Your workspace</a><a href="#faq">Questions & answers</a></div></div>
                <div><h2 class="mb-5 text-sm font-semibold">Make a fresh start</h2><div class="grid gap-3 text-sm text-zinc-600"><a href="{{ route('register') }}" wire:navigate>Create a workspace ↗</a><a href="{{ route('login') }}" wire:navigate>Log in to your account</a></div></div>
            </div>
            <div class="flex flex-wrap justify-between gap-3 pt-7 text-xs text-zinc-500"><p>© {{ date('Y') }} Startsuite. All rights reserved.</p><p>Made for teams that make things.</p></div>
        </footer>
    </div>
    @fluxScripts
</body>
</html>
