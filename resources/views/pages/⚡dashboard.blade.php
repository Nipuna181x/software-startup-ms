<?php

use App\Enums\Role;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    /**
     * Get the real counts for the current organization.
     *
     * Every query runs through the organization global scope, so these numbers
     * only ever describe the viewer's own organization.
     *
     * @return array<int, array{label: string, value: int, hint: string}>
     */
    #[Computed]
    public function stats(): array
    {
        $total = User::query()->count();
        $active = User::query()->where('is_active', true)->count();
        $admins = User::query()->where('role', Role::SuperAdmin)->count();

        return [
            [
                'label' => __('Team members'),
                'value' => $total,
                'hint' => trans_choice('{1}:count active|[2,*]:count active', $active, ['count' => $active]),
            ],
            [
                'label' => __('Super Admins'),
                'value' => $admins,
                'hint' => __('Can manage users and settings'),
            ],
            [
                'label' => __('Deactivated'),
                'value' => $total - $active,
                'hint' => __('Cannot log in'),
            ],
        ];
    }

    /**
     * Get the most recently added members of the organization.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    #[Computed]
    public function recentMembers(): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()->latest()->take(5)->get();
    }
}; ?>

<div class="flex w-full flex-col gap-8">
    <header>
        <h1 class="display text-[30px]">
            {{ __('Welcome back, :name', ['name' => Str::before(auth()->user()->name, ' ')]) }}
        </h1>
        <p class="mt-2 text-sm text-black/55">
            {{ __('This is :organization on Startsuite.', ['organization' => $organization?->name]) }}
        </p>
    </header>

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ($this->stats as $stat)
            <div class="rounded-2xl bg-black/4 p-6 transition-colors hover:bg-black/6">
                <p class="text-xs font-medium tracking-[0.12em] text-black/40 uppercase">
                    {{ $stat['label'] }}
                </p>
                <p class="display mt-4 text-[38px]" style="color:var(--brand)">
                    {{ $stat['value'] }}
                </p>
                <p class="mt-1.5 text-xs text-black/45">{{ $stat['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <section class="overflow-hidden rounded-2xl bg-black/4">
        <header class="flex items-center justify-between px-6 py-5">
            <h2 class="text-[15px] font-medium tracking-tight">{{ __('Recently added') }}</h2>

            @can('super-admin')
                <a
                    href="{{ route('users.index') }}"
                    wire:navigate
                    class="rounded-full bg-black/6 px-4 py-2 text-xs font-medium transition-colors hover:bg-black/10"
                >{{ __('Manage users') }}</a>
            @endcan
        </header>

        <ul class="divide-y divide-black/5 border-t border-black/5">
            @foreach ($this->recentMembers as $member)
                <li class="flex items-center gap-3.5 px-6 py-4">
                    <span
                        class="grid size-9 shrink-0 place-items-center rounded-full text-xs font-semibold"
                        style="background:var(--brand-subtle);color:var(--brand-active)"
                    >{{ $member->initials() }}</span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ $member->name }}</p>
                        <p class="truncate text-xs text-black/45">{{ $member->email }}</p>
                    </div>

                    <span class="rounded-full bg-black/6 px-3 py-1 text-xs text-black/55">
                        {{ $member->role->label() }}
                    </span>
                </li>
            @endforeach
        </ul>
    </section>

    <p class="text-xs text-black/35">
        {{ __('Module widgets will appear here as modules are added.') }}
    </p>
</div>
