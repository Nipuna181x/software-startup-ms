<x-layouts::public :title="__('Startsuite')">
    {{-- Hero: asymmetric, copy left, real UI right --}}
    <section class="mx-auto max-w-6xl px-6 pt-20 pb-24 lg:pt-28">
        <div class="grid items-center gap-16 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <p class="text-xs font-medium tracking-[0.18em] text-zinc-400 uppercase">
                    Workspaces for companies
                </p>

                <h1 class="mt-6 text-5xl leading-[0.98] font-bold tracking-[-0.035em] text-balance lg:text-6xl">
                    Your company gets<br class="hidden sm:block" />
                    its own workspace.
                </h1>

                <p class="mt-7 max-w-md text-lg leading-relaxed text-zinc-600">
                    Register your company and you get a private dashboard carrying your name,
                    your logo and your colours. Add your team, decide who administers it,
                    and nobody outside your company sees any of it.
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a
                        href="{{ route('register') }}"
                        wire:navigate
                        class="rounded-sm bg-[color:var(--color-ink)] px-6 py-3 text-sm font-medium text-white transition-transform hover:-translate-y-px"
                    >Register your company</a>

                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="px-2 py-3 text-sm font-medium text-zinc-600 underline decoration-zinc-300 underline-offset-[6px] transition-colors hover:text-[color:var(--color-ink)] hover:decoration-zinc-500"
                    >I already have an account</a>
                </div>
            </div>

            <div class="lg:col-span-7">
                <div class="relative">
                    <x-dashboard-mockup
                        company="Northwind Logistics"
                        color="#0f766e"
                        initials="NL"
                        class="shadow-[0_1px_2px_rgba(12,20,36,0.04),0_12px_40px_-12px_rgba(12,20,36,0.18)]"
                    />
                    <p class="mt-3 text-xs text-zinc-400">
                        The users screen, as Northwind Logistics sees it.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works: numbered, editorial, no cards --}}
    <section id="how-it-works" class="border-t border-[color:var(--color-rule)]">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-3">
                    <h2 class="text-2xl font-semibold tracking-tight">How it works</h2>
                    <p class="mt-3 text-sm leading-relaxed text-zinc-500">
                        Three steps, about two minutes.
                    </p>
                </div>

                <ol class="lg:col-span-9 lg:grid lg:grid-cols-3 lg:gap-10">
                    @foreach ([
                        ['n' => '01', 'title' => 'Register your company', 'body' => 'Enter your company name, upload a logo if you have one, and pick the colour your team will see every day.'],
                        ['n' => '02', 'title' => 'Get your workspace', 'body' => 'Your dashboard exists the moment you submit. You are its first Super Admin, with full control over it.'],
                        ['n' => '03', 'title' => 'Invite your team', 'body' => 'Add people with their name, email and role. They log in at the same place and land in your workspace.'],
                    ] as $step)
                        <li class="reveal rule-top py-6 lg:py-0 lg:pt-6">
                            <span class="font-mono text-xs tracking-widest text-zinc-400">{{ $step['n'] }}</span>
                            <h3 class="mt-3 text-lg font-semibold tracking-tight">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-zinc-600">{{ $step['body'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- Branding: same UI, three colours --}}
    <section id="branding" class="border-t border-[color:var(--color-rule)] bg-white">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="max-w-2xl">
                <h2 class="text-3xl font-semibold tracking-tight text-balance">
                    The same dashboard, wearing your company's colours.
                </h2>
                <p class="mt-4 text-base leading-relaxed text-zinc-600">
                    Pick a colour once during registration. Startsuite derives the tints, the
                    hover states and the readable text colour from it, then applies them across
                    every screen. Change it later in settings and the whole workspace follows.
                </p>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-3">
                @foreach ([
                    ['company' => 'Northwind Logistics', 'color' => '#0f766e', 'initials' => 'NL'],
                    ['company' => 'Halden & Rowe', 'color' => '#b91c1c', 'initials' => 'HR'],
                    ['company' => 'Meridian Labs', 'color' => '#4338ca', 'initials' => 'ML'],
                ] as $variant)
                    <figure class="reveal">
                        <x-dashboard-mockup
                            :company="$variant['company']"
                            :color="$variant['color']"
                            :initials="$variant['initials']"
                            scale="compact"
                        />
                        <figcaption class="mt-3 flex items-center gap-2 text-xs text-zinc-500">
                            <span class="size-2 rounded-[2px]" style="background:{{ $variant['color'] }}"></span>
                            {{ $variant['company'] }}
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Roadmap --}}
    <section id="roadmap" class="border-t border-[color:var(--color-rule)]">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <h2 class="text-2xl font-semibold tracking-tight">What's coming</h2>
                    <p class="mt-3 text-sm leading-relaxed text-zinc-500">
                        Today Startsuite handles your workspace, your people and your branding.
                        These are the modules being built on top of it.
                    </p>
                </div>

                <div class="lg:col-span-8">
                    <dl class="divide-y divide-[color:var(--color-rule)] border-y border-[color:var(--color-rule)]">
                        @foreach ([
                            ['Calling', 'Place and log calls against your contacts, with the history kept inside your workspace.'],
                            ['Marketing', 'Build a list, send a campaign, and see what happened without leaving the dashboard.'],
                            ['AI assistant', 'Ask questions about your own data and draft the follow-up in the same place.'],
                        ] as $module)
                            <div class="flex flex-col gap-1 py-5 sm:flex-row sm:items-baseline sm:gap-8">
                                <dt class="w-40 shrink-0 font-medium tracking-tight">{{ $module[0] }}</dt>
                                <dd class="text-sm leading-relaxed text-zinc-600">{{ $module[1] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="border-t border-[color:var(--color-rule)] bg-[color:var(--color-ink)]">
        <div class="mx-auto flex max-w-6xl flex-col gap-8 px-6 py-20 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="max-w-lg text-3xl font-semibold tracking-tight text-balance text-white">
                    Register your company and see your own workspace.
                </h2>
                <p class="mt-3 text-sm text-zinc-400">
                    No payment details. You are the Super Admin from the first screen.
                </p>
            </div>

            <a
                href="{{ route('register') }}"
                wire:navigate
                class="shrink-0 self-start rounded-sm bg-white px-6 py-3 text-sm font-medium text-[color:var(--color-ink)] transition-transform hover:-translate-y-px sm:self-auto"
            >Get started</a>
        </div>
    </section>
</x-layouts::public>
