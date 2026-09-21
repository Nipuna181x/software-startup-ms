@props([
    'name' => 'Your company',
    'color' => '#1d4ed8',
    'logoUrl' => null,
])

@php
    use App\Support\Color;
    use Illuminate\Support\Str;

    $ramp = Color::ramp($color);
    $foreground = Color::foregroundFor($ramp['500']);
    $initials = Str::initials($name, true);
@endphp

<div
    {{ $attributes->class(['overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-[0_1px_2px_rgba(12,20,36,0.04),0_12px_32px_-14px_rgba(12,20,36,0.2)]']) }}
    style="--p-500:{{ $ramp['500'] }};--p-600:{{ $ramp['600'] }};--p-50:{{ $ramp['50'] }};--p-100:{{ $ramp['100'] }};--p-fg:{{ $foreground }}"
>
    <div class="flex min-h-[260px]">
        {{-- Sidebar --}}
        <div class="flex w-[45%] shrink-0 flex-col border-e border-zinc-200 bg-zinc-50/80 p-3">
            <div class="flex items-center gap-2">
                @if ($logoUrl)
                    <img
                        src="{{ $logoUrl }}"
                        alt=""
                        class="size-7 shrink-0 rounded object-cover"
                    />
                @else
                    <span
                        class="grid size-7 shrink-0 place-items-center rounded text-[10px] font-semibold"
                        style="background:var(--p-500);color:var(--p-fg)"
                    >{{ $initials }}</span>
                @endif

                <span class="truncate text-[13px] font-semibold tracking-tight text-zinc-800">
                    {{ $name }}
                </span>
            </div>

            <div class="mt-1 ps-9 text-[9px] text-zinc-400">Startsuite</div>

            <div class="mt-5 space-y-1">
                @foreach (['Dashboard', 'Users', 'Settings'] as $index => $item)
                    <div
                        class="flex items-center gap-2 rounded px-2 py-1.5"
                        @if ($index === 0) style="background:var(--p-100)" @endif
                    >
                        <span
                            class="size-2.5 rounded-[3px]"
                            style="background:{{ $index === 0 ? 'var(--p-500)' : '#d4d4d8' }}"
                        ></span>
                        <span
                            class="text-[10px] {{ $index === 0 ? 'font-semibold' : 'text-zinc-500' }}"
                            @if ($index === 0) style="color:var(--p-600)" @endif
                        >{{ $item }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Panel --}}
        <div class="flex min-w-0 flex-1 flex-col p-3">
            <div class="text-[12px] font-semibold tracking-tight text-zinc-800">
                {{ __('Welcome back') }}
            </div>

            <div class="mt-3 grid grid-cols-2 gap-2">
                <div class="rounded border border-zinc-200 p-2">
                    <div class="text-[8px] text-zinc-400">{{ __('Team members') }}</div>
                    <div class="mt-0.5 text-[14px] font-semibold" style="color:var(--p-600)">1</div>
                </div>
                <div class="rounded border border-zinc-200 p-2">
                    <div class="text-[8px] text-zinc-400">{{ __('Super Admins') }}</div>
                    <div class="mt-0.5 text-[14px] font-semibold text-zinc-800">1</div>
                </div>
            </div>

            <div
                class="mt-3 w-fit rounded px-2.5 py-1 text-[9px] font-medium"
                style="background:var(--p-500);color:var(--p-fg)"
            >{{ __('Add user') }}</div>

            <div class="mt-3 space-y-1.5">
                @foreach ([70, 52, 61] as $width)
                    <div class="h-1.5 rounded-full bg-zinc-100" style="width: {{ $width }}%"></div>
                @endforeach
            </div>
        </div>
    </div>
</div>
