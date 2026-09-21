<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white text-black antialiased">
        <div class="flex min-h-svh flex-col">
            <header class="border-b border-black/6">
                <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-5 sm:px-8">
                    <a href="{{ route('home') }}" wire:navigate class="transition-opacity hover:opacity-60">
                        <x-wordmark size="base" />
                        <span class="sr-only">Startsuite home</span>
                    </a>

                    <a
                        href="{{ route('home') }}"
                        wire:navigate
                        class="rounded-full bg-black/4 px-4 py-2 text-sm font-medium text-black/60 transition-colors hover:bg-black/6 hover:text-black"
                    >Back to site</a>
                </div>
            </header>

            <div class="flex flex-1 items-center justify-center px-5 py-14 sm:px-8">
                <div class="w-full {{ $wide ?? false ? 'max-w-5xl' : 'max-w-[400px]' }}">
                    {{ $slot }}
                </div>
            </div>

            <footer class="border-t border-black/6">
                <div class="mx-auto max-w-6xl px-5 py-6 text-xs text-black/40 sm:px-8">
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
