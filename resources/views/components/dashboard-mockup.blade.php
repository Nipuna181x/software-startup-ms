@props([
    'company' => 'Northwind Logistics',
    'color' => '#0f766e',
    'initials' => 'NL',
    'scale' => 'full',
])

@php
    use App\Support\Color;

    $ramp = Color::ramp($color);
    $foreground = Color::foregroundFor($ramp['500']);

    $style = collect([
        '--m-500' => $ramp['500'],
        '--m-600' => $ramp['600'],
        '--m-50' => $ramp['50'],
        '--m-100' => $ramp['100'],
        '--m-200' => $ramp['200'],
        '--m-fg' => $foreground,
    ])->map(fn ($value, $name) => "{$name}:{$value}")->implode(';');

    $rows = [
        ['name' => 'Amara Osei', 'email' => 'amara@northwind.co', 'role' => 'Super Admin', 'active' => true],
        ['name' => 'Tom Beckett', 'email' => 'tom@northwind.co', 'role' => 'User', 'active' => true],
        ['name' => 'Priya Raman', 'email' => 'priya@northwind.co', 'role' => 'User', 'active' => true],
        ['name' => 'Lukas Vogel', 'email' => 'lukas@northwind.co', 'role' => 'User', 'active' => false],
    ];

    $compact = $scale === 'compact';
@endphp

<div
    style="{{ $style }}"
    {{ $attributes->class(['overflow-hidden rounded-lg border border-zinc-200 bg-white']) }}
    aria-hidden="true"
>
    <div class="flex {{ $compact ? 'h-44' : 'h-full min-h-[320px]' }}">
        {{-- Sidebar --}}
        <div class="flex w-[34%] shrink-0 flex-col border-e border-zinc-200 bg-zinc-50/80 {{ $compact ? 'p-2' : 'p-3' }}">
            <div class="flex items-center gap-1.5">
                <span
                    class="grid shrink-0 place-items-center rounded font-semibold {{ $compact ? 'size-4 text-[6px]' : 'size-6 text-[9px]' }}"
                    style="background:var(--m-500);color:var(--m-fg)"
                >{{ $initials }}</span>
                <span class="truncate font-semibold tracking-tight text-zinc-800 {{ $compact ? 'text-[7px]' : 'text-[11px]' }}">
                    {{ $company }}
                </span>
            </div>

            <div class="{{ $compact ? 'mt-0.5 ps-[22px] text-[5px]' : 'mt-1 ps-[30px] text-[7px]' }} text-zinc-400">
                Startsuite
            </div>

            <div class="{{ $compact ? 'mt-2 space-y-0.5' : 'mt-4 space-y-1' }}">
                @foreach (['Dashboard', 'Users', 'Settings'] as $index => $item)
                    <div
                        class="flex items-center gap-1.5 rounded {{ $compact ? 'px-1 py-0.5' : 'px-1.5 py-1' }}"
                        @if ($index === 1) style="background:var(--m-100)" @endif
                    >
                        <span
                            class="rounded-[2px] {{ $compact ? 'size-1.5' : 'size-2' }}"
                            style="background:{{ $index === 1 ? 'var(--m-500)' : '#d4d4d8' }}"
                        ></span>
                        <span
                            class="{{ $compact ? 'text-[6px]' : 'text-[8px]' }} {{ $index === 1 ? 'font-semibold' : 'text-zinc-500' }}"
                            @if ($index === 1) style="color:var(--m-600)" @endif
                        >{{ $item }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Main panel --}}
        <div class="flex min-w-0 flex-1 flex-col {{ $compact ? 'p-2' : 'p-3' }}">
            <div class="flex items-center justify-between">
                <div class="font-semibold tracking-tight text-zinc-800 {{ $compact ? 'text-[7px]' : 'text-[10px]' }}">
                    Users
                </div>
                <div
                    class="rounded font-medium {{ $compact ? 'px-1 py-[2px] text-[5px]' : 'px-1.5 py-[3px] text-[7px]' }}"
                    style="background:var(--m-500);color:var(--m-fg)"
                >Add user</div>
            </div>

            <div class="{{ $compact ? 'mt-1.5' : 'mt-2.5' }} overflow-hidden rounded border border-zinc-200">
                <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50 {{ $compact ? 'px-1.5 py-[3px]' : 'px-2 py-1' }}">
                    <div class="flex-1 {{ $compact ? 'text-[5px]' : 'text-[7px]' }} font-medium text-zinc-500">Name</div>
                    <div class="w-10 {{ $compact ? 'text-[5px]' : 'text-[7px]' }} font-medium text-zinc-500">Role</div>
                    <div class="w-8 {{ $compact ? 'text-[5px]' : 'text-[7px]' }} font-medium text-zinc-500">Status</div>
                </div>

                @foreach ($rows as $row)
                    @if (! ($compact && $loop->index > 2))
                        <div class="flex items-center gap-2 border-b border-zinc-100 last:border-0 {{ $compact ? 'px-1.5 py-[3px]' : 'px-2 py-1.5' }}">
                            <div class="flex min-w-0 flex-1 items-center gap-1.5">
                                <span
                                    class="grid shrink-0 place-items-center rounded-full font-semibold {{ $compact ? 'size-2.5 text-[4px]' : 'size-4 text-[6px]' }}"
                                    style="background:var(--m-50);color:var(--m-600)"
                                >{{ Str::initials($row['name'], true) }}</span>
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-zinc-700 {{ $compact ? 'text-[5px]' : 'text-[7px]' }}">{{ $row['name'] }}</div>
                                    @unless ($compact)
                                        <div class="truncate text-[6px] text-zinc-400">{{ $row['email'] }}</div>
                                    @endunless
                                </div>
                            </div>
                            <div class="w-10 {{ $compact ? 'text-[5px]' : 'text-[7px]' }} text-zinc-500">{{ $row['role'] }}</div>
                            <div class="w-8">
                                <span
                                    class="inline-block rounded-full {{ $compact ? 'px-1 py-[1px] text-[4px]' : 'px-1.5 py-[1px] text-[6px]' }} font-medium"
                                    @if ($row['active'])
                                        style="background:var(--m-50);color:var(--m-600)"
                                    @else
                                        style="background:#f4f4f5;color:#a1a1aa"
                                    @endif
                                >{{ $row['active'] ? 'Active' : 'Off' }}</span>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
