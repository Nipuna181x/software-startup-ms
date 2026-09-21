<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-black antialiased">
        <header class="sticky top-0 z-50 border-b border-black/6 bg-white/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-5 sm:px-8">
                <a href="{{ route('home') }}" wire:navigate class="transition-opacity hover:opacity-60">
                    <x-wordmark size="base" />
                    <span class="sr-only">Startsuite home</span>
                </a>

                <nav class="hidden items-center gap-8 text-sm text-black/60 md:flex">
                    <a class="transition-colors hover:text-black" href="{{ route('home') }}#how-it-works" wire:navigate>How it works</a>
                    <a class="transition-colors hover:text-black" href="{{ route('home') }}#branding" wire:navigate>Branding</a>
                    <a class="transition-colors hover:text-black" href="{{ route('home') }}#roadmap" wire:navigate>What's next</a>
                </nav>

                <div class="flex items-center gap-2">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            wire:navigate
                            class="rounded-full bg-black px-5 py-2.5 text-sm font-medium text-white transition-opacity hover:opacity-80"
                        >Dashboard</a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            wire:navigate
                            class="rounded-full px-4 py-2.5 text-sm font-medium text-black/60 transition-colors hover:text-black"
                        >Log in</a>

                        <a
                            href="{{ route('register') }}"
                            wire:navigate
                            class="rounded-full bg-black px-5 py-2.5 text-sm font-medium text-white transition-opacity hover:opacity-80"
                        >Get started</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-28 border-t border-black/6">
            <div class="mx-auto max-w-6xl px-5 py-14 sm:px-8">
                <div class="flex flex-col gap-10 sm:flex-row sm:items-start sm:justify-between">
                    <div class="max-w-xs">
                        <x-wordmark size="sm" />
                        <p class="mt-3 text-sm leading-relaxed text-black/50">
                            A private, branded workspace for your company and the people in it.
                        </p>
                    </div>

                    <nav class="flex gap-14 text-sm">
                        <div>
                            <p class="mb-3.5 font-medium text-black/40">Product</p>
                            <ul class="space-y-2.5">
                                <li><a class="text-black/60 transition-colors hover:text-black" href="{{ route('home') }}#how-it-works" wire:navigate>How it works</a></li>
                                <li><a class="text-black/60 transition-colors hover:text-black" href="{{ route('home') }}#branding" wire:navigate>Branding</a></li>
                                <li><a class="text-black/60 transition-colors hover:text-black" href="{{ route('home') }}#roadmap" wire:navigate>What's next</a></li>
                            </ul>
                        </div>

                        <div>
                            <p class="mb-3.5 font-medium text-black/40">Access</p>
                            <ul class="space-y-2.5">
                                <li><a class="text-black/60 transition-colors hover:text-black" href="{{ route('register') }}" wire:navigate>Register</a></li>
                                <li><a class="text-black/60 transition-colors hover:text-black" href="{{ route('login') }}" wire:navigate>Log in</a></li>
                            </ul>
                        </div>
                    </nav>
                </div>

                <p class="mt-14 border-t border-black/6 pt-6 text-xs text-black/40">
                    &copy; {{ date('Y') }} Startsuite
                </p>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
