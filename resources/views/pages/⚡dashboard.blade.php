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

<div class="mx-auto grid max-w-[1440px] gap-8 xl:grid-cols-[minmax(0,1fr)_280px] xl:gap-9">
    <div class="min-w-0">
        <header class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-200/80 pb-8">
            <div><p class="eyebrow">Your workspace, at a glance</p><h1 class="display mt-3 text-3xl sm:text-4xl">{{ __('Hello, :name', ['name' => Str::before(auth()->user()->name, ' ')]) }}<span class="text-zinc-400">.</span></h1><p class="mt-3 text-sm text-zinc-500">A little clarity for the day ahead. Let’s make it a good one.</p></div>
            <div class="flex items-center gap-3 text-xs text-zinc-500"><time datetime="{{ now()->toDateString() }}">{{ now()->format('d M, Y') }}</time><span class="grid size-10 place-items-center rounded-full bg-white"><flux:icon icon="calendar-days" class="size-5 text-zinc-700"/></span></div>
        </header>
        <div class="grid gap-5 border-b border-zinc-200/80 py-7 sm:grid-cols-3">
            @foreach ($this->stats as $stat)
                <div wire:key="stat-{{ $loop->index }}" class="flex gap-3"><span @class(['grid size-11 shrink-0 place-items-center rounded-full', 'bg-[#e7f1fa]' => $loop->first, 'bg-[#fff0d5]' => $loop->index === 1, 'bg-[#eee9f8]' => $loop->last])><flux:icon :icon="['users', 'shield-check', 'pause-circle'][$loop->index]" class="size-5"/></span><div><p class="text-xs text-zinc-500">{{ $stat['label'] }}</p><p class="mt-1 text-2xl font-semibold tracking-tight">{{ $stat['value'] }}</p><p class="mt-1 text-[10px] text-zinc-500">{{ $stat['hint'] }}</p></div></div>
            @endforeach
        </div>
        <section class="mt-8">
            <header class="flex items-center justify-between gap-4"><h2 class="text-lg font-medium tracking-tight">Make yourself at home</h2><span class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-[10px] text-zinc-500">Getting started</span></header>
            <div class="panel mt-5 overflow-hidden">
                <div class="flex items-center gap-5 bg-[#f2f6ed] p-6"><span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-white"><flux:icon icon="check-circle" class="size-6 text-green-700"/></span><div><p class="text-sm font-medium">Your workspace is ready.</p><p class="mt-1 text-xs leading-5 text-zinc-500">{{ $organization?->name }} has a place to come together.</p></div></div>
                <div class="divide-y divide-zinc-100 px-5">
                    @can('super-admin')
                        <a href="{{ route('organization.edit') }}" wire:navigate class="flex items-center gap-4 py-5"><span class="grid size-9 shrink-0 place-items-center rounded-full bg-[#fff0d5]"><flux:icon icon="swatch" class="size-4"/></span><div class="flex-1"><h3 class="text-sm font-medium">Add your own personality</h3><p class="mt-1 text-xs text-zinc-500">Set your company logo and brand color.</p></div><flux:icon icon="arrow-up-right" class="size-4 text-zinc-400"/></a>
                        <a href="{{ route('users.index') }}" wire:navigate class="flex items-center gap-4 py-5"><span class="grid size-9 shrink-0 place-items-center rounded-full bg-[#e7f1fa]"><flux:icon icon="user-plus" class="size-4"/></span><div class="flex-1"><h3 class="text-sm font-medium">Bring your people together</h3><p class="mt-1 text-xs text-zinc-500">Add team members and give everyone the right access.</p></div><flux:icon icon="arrow-up-right" class="size-4 text-zinc-400"/></a>
                    @endcan
                    <a href="{{ route('profile.edit') }}" wire:navigate class="flex items-center gap-4 py-5"><span class="grid size-9 shrink-0 place-items-center rounded-full bg-[#eee9f8]"><flux:icon icon="user-circle" class="size-4"/></span><div class="flex-1"><h3 class="text-sm font-medium">Make sure your details are up to date</h3><p class="mt-1 text-xs text-zinc-500">Manage your profile and keep your account secure.</p></div><flux:icon icon="arrow-up-right" class="size-4 text-zinc-400"/></a>
                </div>
            </div>
        </section>
        <section class="mt-8">
            <header class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-lg font-medium tracking-tight">Recently added</h2>@can('super-admin')<a href="{{ route('users.index') }}" wire:navigate class="text-xs font-medium text-zinc-500 hover:text-zinc-950">Manage team <flux:icon icon="arrow-up-right" class="inline-block size-4 shrink-0 align-text-bottom" aria-hidden="true" /></a>@endcan</header>
            <ul class="mt-4 divide-y divide-zinc-100">
                @foreach ($this->recentMembers as $member)
                    <li wire:key="member-{{ $member->id }}" class="flex items-center gap-3 py-4"><span class="grid size-10 shrink-0 place-items-center rounded-full bg-brand-subtle text-xs font-medium text-brand-active">{{ $member->initials() }}</span><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ $member->name }}</p><p class="mt-1 truncate text-xs text-zinc-500">{{ $member->email }}</p></div><span class="hidden rounded-full bg-white px-3 py-1.5 text-[10px] text-zinc-500 sm:block">{{ $member->role->label() }}</span><span class="flex items-center gap-1.5 text-[10px] text-zinc-500"><span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $member->is_active, 'bg-zinc-400' => !$member->is_active])></span>{{ $member->is_active ? 'Active' : 'Inactive' }}</span></li>
                @endforeach
            </ul>
        </section>
    </div>
    <aside class="grid content-start gap-7 border-zinc-200/80 xl:border-l xl:pl-8">
        <section class="rounded-2xl bg-[#f0f1ee] px-5 py-7 text-center"><span class="relative mx-auto grid size-20 place-items-center rounded-full border-4 border-white bg-[#f8e8ce] text-2xl font-medium">{{ auth()->user()->initials() }}<span class="absolute right-0 bottom-0 size-4 rounded-full border-2 border-white bg-emerald-400" title="Account active"></span></span><h2 class="mt-4 text-base font-medium">{{ auth()->user()->name }}</h2><p class="mt-1 break-all text-xs text-zinc-500">{{ auth()->user()->email }}</p><span class="mt-4 inline-flex rounded-full bg-white px-3 py-1.5 text-[10px]">{{ auth()->user()->role->label() }}</span><a href="{{ route('profile.edit') }}" wire:navigate class="mt-5 flex items-center justify-center gap-2 text-xs font-medium">Edit profile <flux:icon icon="arrow-up-right" class="size-3"/></a></section>
        <section><h2 class="flex items-center gap-3 text-sm font-medium"><span class="h-px flex-1 bg-zinc-200"></span>Workspace notes<span class="h-px flex-1 bg-zinc-200"></span></h2><div class="mt-6 flex gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-[#e7f1fa]"><flux:icon icon="building-office-2" class="size-4"/></span><div><p class="text-xs font-medium">A place for {{ $organization?->name }}</p><p class="mt-2 text-xs leading-6 text-zinc-500">Workspace created {{ $organization?->created_at?->format('d M Y') }}.</p></div></div><div class="mt-6 flex gap-3"><span class="grid size-8 shrink-0 place-items-center rounded-full bg-[#eee9f8]"><flux:icon icon="shield-check" class="size-4"/></span><div><p class="text-xs font-medium">Your team’s own space</p><p class="mt-2 text-xs leading-6 text-zinc-500">Your workspace data stays separate from other organizations.</p></div></div></section>
        <section class="rounded-2xl border border-dashed border-zinc-300 p-5"><span class="rounded-full bg-[#fff0d5] px-3 py-1 text-[10px]">Up next</span><h2 class="mt-4 text-sm font-medium">More room for great work.</h2><p class="mt-2 text-xs leading-6 text-zinc-500">Planning, projects, code reviews, issues, and documentation are on the way.</p><p class="mt-4 text-[10px] text-zinc-400">These tools are not available yet.</p></section>
    </aside>
</div>
