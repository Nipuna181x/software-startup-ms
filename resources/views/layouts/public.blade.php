<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[color:var(--color-paper)] text-[color:var(--color-ink)] antialiased">
        <header class="sticky top-0 z-40 border-b border-[color:var(--color-rule)] bg-[color:var(--color-paper)]/85 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                <a href="{{ route('home') }}" wire:navigate class="transition-opacity hover:opacity-70">
                    <x-wordmark size="base" />
                    <span class="sr-only">Startsuite home</span>
                </a>

                <nav class="flex items-center gap-1 text-sm">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            wire:navigate
                            class="rounded-sm px-3 py-2 font-medium text-[color:var(--color-ink)] transition-colors hover:bg-zinc-900/5"
                        >Dashboard</a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            wire:navigate
                            class="rounded-sm px-3 py-2 font-medium text-zinc-600 transition-colors hover:text-[color:var(--color-ink)]"
                        >Log in</a>

                        <a
                            href="{{ route('register') }}"
                            wire:navigate
                            class="ms-1 rounded-sm bg-[color:var(--color-ink)] px-4 py-2 font-medium text-white transition-transform hover:-translate-y-px"
                        >Register your company</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>
            {{ $slot }}
        </main>

        <footer class="mt-24 border-t border-[color:var(--color-rule)]">
            <div class="mx-auto max-w-6xl px-6 py-12">
                <div class="flex flex-col gap-8 sm:flex-row sm:items-start sm:justify-between">
                    <div class="max-w-xs">
                        <x-wordmark size="sm" />
                        <p class="mt-3 text-sm leading-relaxed text-zinc-500">
                            A private, branded workspace for your company and the people in it.
                        </p>
                    </div>

                    <nav class="flex gap-12 text-sm">
                        <div>
                            <p class="mb-3 font-medium text-zinc-400">Product</p>
                            <ul class="space-y-2">
                                <li><a class="text-zinc-600 hover:text-[color:var(--color-ink)]" href="{{ route('home') }}#how-it-works" wire:navigate>How it works</a></li>
                                <li><a class="text-zinc-600 hover:text-[color:var(--color-ink)]" href="{{ route('home') }}#branding" wire:navigate>Branding</a></li>
                                <li><a class="text-zinc-600 hover:text-[color:var(--color-ink)]" href="{{ route('home') }}#roadmap" wire:navigate>What's next</a></li>
                            </ul>
                        </div>

                        <div>
                            <p class="mb-3 font-medium text-zinc-400">Access</p>
                            <ul class="space-y-2">
                                <li><a class="text-zinc-600 hover:text-[color:var(--color-ink)]" href="{{ route('register') }}" wire:navigate>Register</a></li>
                                <li><a class="text-zinc-600 hover:text-[color:var(--color-ink)]" href="{{ route('login') }}" wire:navigate>Log in</a></li>
                            </ul>
                        </div>
                    </nav>
                </div>

                <p class="mt-12 border-t border-[color:var(--color-rule)] pt-6 text-xs text-zinc-400">
                    &copy; {{ date('Y') }} Startsuite
                </p>
            </div>
        </footer>

        @fluxScripts
    </body>
</html>
