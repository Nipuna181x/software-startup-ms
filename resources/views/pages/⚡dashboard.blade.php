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
        <h1 class="text-2xl font-semibold tracking-tight">
            {{ __('Welcome back, :name', ['name' => Str::before(auth()->user()->name, ' ')]) }}
        </h1>
        <p class="mt-1 text-sm text-zinc-500">
            {{ __('This is :organization on Startsuite.', ['organization' => $organization?->name]) }}
        </p>
    </header>

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ($this->stats as $stat)
            <div class="rounded-lg border border-zinc-200 p-5">
                <p class="text-xs font-medium tracking-[0.12em] text-zinc-400 uppercase">
                    {{ $stat['label'] }}
                </p>
                <p class="mt-3 text-3xl font-semibold tracking-tight text-[color:var(--brand)]">
                    {{ $stat['value'] }}
                </p>
                <p class="mt-1 text-xs text-zinc-500">{{ $stat['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <section class="rounded-lg border border-zinc-200">
        <header class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
            <h2 class="text-sm font-semibold tracking-tight">{{ __('Recently added') }}</h2>

            @can('super-admin')
                <flux:link :href="route('users.index')" wire:navigate class="text-sm">
                    {{ __('Manage users') }}
                </flux:link>
            @endcan
        </header>

        <ul class="divide-y divide-zinc-100">
            @foreach ($this->recentMembers as $member)
                <li class="flex items-center gap-3 px-5 py-3">
                    <span
                        class="grid size-8 shrink-0 place-items-center rounded-full text-xs font-semibold"
                        style="background:var(--brand-subtle);color:var(--brand-hover)"
                    >{{ $member->initials() }}</span>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-zinc-800">{{ $member->name }}</p>
                        <p class="truncate text-xs text-zinc-500">{{ $member->email }}</p>
                    </div>

                    <span class="text-xs text-zinc-400">{{ $member->role->label() }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <p class="text-xs text-zinc-400">
        {{ __('Module widgets will appear here as modules are added.') }}
    </p>
</div>
