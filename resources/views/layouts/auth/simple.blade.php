<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[color:var(--color-paper)] text-[color:var(--color-ink)] antialiased">
        <div class="flex min-h-svh flex-col">
            <header class="border-b border-[color:var(--color-rule)]">
                <div class="mx-auto flex h-16 max-w-6xl items-center px-6">
                    <a href="{{ route('home') }}" wire:navigate class="transition-opacity hover:opacity-70">
                        <x-wordmark size="base" />
                        <span class="sr-only">Startsuite home</span>
                    </a>
                </div>
            </header>

            <div class="flex flex-1 items-center justify-center px-6 py-12">
                <div class="w-full {{ $wide ?? false ? 'max-w-4xl' : 'max-w-sm' }}">
                    {{ $slot }}
                </div>
            </div>

            <footer class="border-t border-[color:var(--color-rule)]">
                <div class="mx-auto max-w-6xl px-6 py-6 text-xs text-zinc-400">
                    &copy; {{ date('Y') }} Startsuite
                </div>
            </footer>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
