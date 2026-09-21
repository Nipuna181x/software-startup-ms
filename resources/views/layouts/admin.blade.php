<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>@include('partials.head')</head>
<body class="min-h-screen bg-[#f6f7f4] text-zinc-950 antialiased">
    <a href="#main-content" class="skip-link">Skip to content</a>
    <header class="border-b border-zinc-200 bg-white"><div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-5 sm:px-10"><a href="{{ route('admin.dashboard') }}" aria-label="Startsuite admin"><x-wordmark /></a><div class="flex items-center gap-4"><span class="hidden rounded-full bg-[#eef6e5] px-3 py-1.5 text-[10px] font-medium sm:block">Platform Super Admin</span><form action="{{ route('admin.logout') }}" method="POST">@csrf<button type="submit" class="button-outline !min-h-9 !px-3 !py-2 !text-xs">Log out <flux:icon icon="arrow-right-start-on-rectangle" class="size-4"/></button></form></div></div></header>
    <main id="main-content" class="mx-auto max-w-7xl px-5 py-9 sm:px-10 sm:py-12">{{ $slot }}</main>
    <footer class="mx-auto flex max-w-7xl flex-wrap justify-between gap-3 px-5 py-8 text-[11px] text-zinc-500 sm:px-10"><span>Startsuite · Platform administration</span><a href="{{ route('home') }}">Visit website ↗</a></footer>
    @fluxScripts
</body>
</html>
