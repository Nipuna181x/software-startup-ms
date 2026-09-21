<x-layouts::public :title="__('Startsuite')">
    {{-- Hero --}}
    <section class="mx-auto max-w-6xl px-5 pt-20 pb-24 sm:px-8 lg:pt-28">
        <div class="mx-auto max-w-3xl text-center">
            <a
                href="{{ route('home') }}#roadmap"
                wire:navigate
                class="inline-flex items-center gap-2 rounded-full bg-black/4 px-3.5 py-1.5 text-xs font-medium text-black/60 transition-colors hover:bg-black/6"
            >
                <span class="size-1.5 rounded-full bg-black/40"></span>
                Calling, marketing and AI modules coming next
            </a>

            <h1 class="display mt-7 text-[44px] sm:text-[58px] lg:text-[68px]">
                Your company gets its
                <span class="gradient-ink">own workspace</span>
            </h1>

            <p class="mx-auto mt-6 max-w-xl text-base leading-relaxed text-black/60 sm:text-lg">
                Register your company and get a private dashboard carrying your name,
                your logo and your colours. Add your team, decide who administers it,
                and nobody outside your company sees any of it.
            </p>

            <div class="mt-9 flex flex-wrap items-center justify-center gap-3">
                <a
                    href="{{ route('register') }}"
                    wire:navigate
                    class="rounded-full bg-black px-6 py-3 text-sm font-medium text-white transition-opacity hover:opacity-80"
                >Register your company</a>

                <a
                    href="{{ route('login') }}"
                    wire:navigate
                    class="rounded-full bg-black/4 px-6 py-3 text-sm font-medium text-black transition-colors hover:bg-black/6"
                >I already have an account</a>
            </div>
        </div>

        {{-- Real UI as the hero visual --}}
        <div class="reveal mt-16">
            <div class="rounded-2xl bg-black/4 p-2 sm:p-3">
                <x-dashboard-mockup
                    company="Northwind Logistics"
                    color="#0f766e"
                    initials="NL"
                    class="!rounded-xl !border-black/8 shadow-[0_1px_2px_rgba(0,0,0,0.04),0_24px_60px_-24px_rgba(0,0,0,0.25)]"
                />
            </div>
            <p class="mt-4 text-center text-sm text-black/40">
                The users screen, as Northwind Logistics sees it.
            </p>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
        <div class="max-w-2xl">
            <h2 class="display text-3xl sm:text-4xl">How it works</h2>
            <p class="mt-4 text-base leading-relaxed text-black/60">
                Three steps, about two minutes.
            </p>
        </div>

        <ol class="mt-12 grid gap-4 md:grid-cols-3">
            @foreach ([
                ['n' => '01', 'title' => 'Register your company', 'body' => 'Enter your company name, upload a logo if you have one, and pick the colour your team will see every day.'],
                ['n' => '02', 'title' => 'Get your workspace', 'body' => 'Your dashboard exists the moment you submit. You are its first Super Admin, with full control over it.'],
                ['n' => '03', 'title' => 'Invite your team', 'body' => 'Add people with their name, email and role. They log in at the same place and land in your workspace.'],
            ] as $step)
                <li class="reveal rounded-2xl bg-black/4 p-6 transition-colors hover:bg-black/6">
                    <span class="text-xs font-medium tracking-widest text-black/35">{{ $step['n'] }}</span>
                    <h3 class="mt-4 text-lg font-medium tracking-tight">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-black/60">{{ $step['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Branding --}}
    <section id="branding" class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
        <div class="max-w-2xl">
            <h2 class="display text-3xl sm:text-4xl">
                The same dashboard, wearing
                <span class="gradient-ink">your company's colours</span>
            </h2>
            <p class="mt-4 text-base leading-relaxed text-black/60">
                Pick a colour once during registration. Startsuite derives the tints, the
                hover states and the readable text colour from it, then applies them across
                every screen. Change it later in settings and the whole workspace follows.
            </p>
        </div>

        <div class="mt-12 grid gap-5 sm:grid-cols-3">
            @foreach ([
                ['company' => 'Northwind Logistics', 'color' => '#0f766e', 'initials' => 'NL'],
                ['company' => 'Halden & Rowe', 'color' => '#b91c1c', 'initials' => 'HR'],
                ['company' => 'Meridian Labs', 'color' => '#4338ca', 'initials' => 'ML'],
            ] as $variant)
                <figure class="reveal">
                    <div class="rounded-2xl bg-black/4 p-2">
                        <x-dashboard-mockup
                            :company="$variant['company']"
                            :color="$variant['color']"
                            :initials="$variant['initials']"
                            scale="compact"
                            class="!rounded-xl !border-black/8"
                        />
                    </div>
                    <figcaption class="mt-3 flex items-center gap-2 px-1 text-sm text-black/50">
                        <span class="size-2.5 rounded-full" style="background:{{ $variant['color'] }}"></span>
                        {{ $variant['company'] }}
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </section>

    {{-- Roadmap --}}
    <section id="roadmap" class="mx-auto max-w-6xl px-5 py-20 sm:px-8">
        <div class="grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <h2 class="display text-3xl sm:text-4xl">What's coming</h2>
                <p class="mt-4 text-base leading-relaxed text-black/60">
                    Today Startsuite handles your workspace, your people and your branding.
                    These are the modules being built on top of it.
                </p>
            </div>

            <div class="lg:col-span-8">
                <dl class="space-y-3">
                    @foreach ([
                        ['Calling', 'Place and log calls against your contacts, with the history kept inside your workspace.'],
                        ['Marketing', 'Build a list, send a campaign, and see what happened without leaving the dashboard.'],
                        ['AI assistant', 'Ask questions about your own data and draft the follow-up in the same place.'],
                    ] as $module)
                        <div class="reveal flex flex-col gap-2 rounded-2xl bg-black/4 p-6 sm:flex-row sm:items-baseline sm:gap-8">
                            <dt class="w-36 shrink-0 font-medium tracking-tight">{{ $module[0] }}</dt>
                            <dd class="text-sm leading-relaxed text-black/60">{{ $module[1] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="mx-auto max-w-6xl px-5 pb-8 sm:px-8">
        <div class="rounded-3xl bg-black px-8 py-16 text-center sm:px-16 sm:py-20">
            <h2 class="display mx-auto max-w-2xl text-3xl text-white sm:text-[42px]">
                Register your company and see your own workspace
            </h2>
            <p class="mx-auto mt-5 max-w-md text-sm text-white/50">
                No payment details. You are the Super Admin from the first screen.
            </p>

            <a
                href="{{ route('register') }}"
                wire:navigate
                class="mt-9 inline-block rounded-full bg-white px-7 py-3 text-sm font-medium text-black transition-opacity hover:opacity-85"
            >Get started</a>
        </div>
    </section>
</x-layouts::public>
