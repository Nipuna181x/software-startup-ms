@props([
    'name' => 'Your company',
    'logoUrl' => null,
])

@php
    use Illuminate\Support\Str;

    $initials = Str::initials($name, true);
@endphp

<div {{ $attributes->class(['overflow-hidden rounded-xl border border-black/8 bg-white']) }}>
    <div class="flex min-h-[260px]">
        {{-- Sidebar --}}
        <div class="flex w-[45%] shrink-0 flex-col border-e border-black/6 bg-black/[0.03] p-3">
            <div class="flex items-center gap-2">
                @if ($logoUrl)
                    <img
                        src="{{ $logoUrl }}"
                        alt=""
                        class="size-7 shrink-0 rounded object-cover"
                    />
                @else
                    <span class="grid size-7 shrink-0 place-items-center rounded bg-brand text-[10px] font-semibold text-brand-foreground">{{ $initials }}</span>
                @endif

                <span class="truncate text-[13px] font-semibold tracking-tight text-black/85">
                    {{ $name }}
                </span>
            </div>

            <div class="mt-1 ps-9 text-[9px] text-black/40">Startsuite</div>

            <div class="mt-5 space-y-1">
                @foreach (['Dashboard', 'Users', 'Settings'] as $index => $item)
                    <div @class([
                        'flex items-center gap-2 rounded px-2 py-1.5',
                        'bg-brand-subtle' => $index === 0,
                    ])>
                        <span
                            class="size-2.5 rounded-[3px]"
                            style="background:{{ $index === 0 ? 'var(--brand)' : '#d4d4d8' }}"
                        ></span>
                        <span @class([
                            'text-[10px]',
                            'font-semibold text-brand-active' => $index === 0,
                            'text-black/50' => $index !== 0,
                        ])>{{ $item }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Panel --}}
        <div class="flex min-w-0 flex-1 flex-col p-3">
            <div class="text-[12px] font-semibold tracking-tight text-black/85">
                {{ __('Welcome back') }}
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2">
                <div class="rounded border border-black/8 p-2">
                    <div class="text-[8px] text-black/40">{{ __('Team members') }}</div>
                    <div class="mt-0.5 text-[14px] font-semibold text-brand-active">1</div>
                </div>
                <div class="rounded border border-black/8 p-2">
                    <div class="text-[8px] text-black/40">{{ __('Super Admins') }}</div>
                    <div class="mt-0.5 text-[14px] font-semibold text-black/85">1</div>
                </div>
            </div>

            <div class="mt-3 w-fit rounded bg-brand px-2.5 py-1 text-[9px] font-medium text-brand-foreground">{{ __('Add user') }}</div>

            <div class="mt-3 space-y-1.5">
                @foreach ([70, 52, 61] as $width)
                    <div class="h-1.5 rounded-full bg-black/6" style="width: {{ $width }}%"></div>
                @endforeach
            </div>
        </div>
    </div>
</div>
