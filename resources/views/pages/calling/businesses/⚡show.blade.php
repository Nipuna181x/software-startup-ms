<?php

use App\Concerns\Calling\LogsCalls;
use App\Models\Calling\Business;
use App\Models\Calling\CallBusinessType;
use App\Models\Calling\CallCategory;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    use LogsCalls;

    public CallCategory $category;

    public CallBusinessType $type;

    public Business $business;

    /**
     * Authorize the page and bind the route models.
     */
    public function mount(CallCategory $category, CallBusinessType $type, Business $business): void
    {
        $this->authorize('view', $business);

        $this->category = $category;
        $this->type = $type;
        $this->business = $business->load(['phones' => fn ($query) => $query->orderByDesc('is_primary'), 'screenshots']);
    }

    /**
     * Render the component with a dynamic page title.
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        return $this->view()->title($this->business->name);
    }

    /**
     * Get the business's full call history, newest first.
     */
    #[Computed]
    public function callHistory(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->business->calls()->with(['user', 'phone'])->get();
    }

    /**
     * Reload the business and its history after a call is logged.
     */
    protected function afterCallLogged(): void
    {
        $this->business = $this->business->fresh(['phones', 'screenshots']);
        unset($this->callHistory);
    }
}; ?>

<div class="flex w-full flex-col gap-6">
    <x-breadcrumbs :trail="[
        ['label' => __('Calling'), 'route' => route('calling.categories.index')],
        ['label' => $category->name, 'route' => route('calling.types.index', $category)],
        ['label' => $type->name, 'route' => route('calling.businesses.index', [$category, $type])],
        ['label' => $business->name, 'route' => null],
    ]" />

    <header class="flex flex-wrap items-center gap-3">
        <h1 class="display text-[30px]">{{ $business->name }}</h1>

        @if ($business->hasBeenAnswered())
            <flux:badge size="sm" :color="$business->currentStatus()->badgeColor()">
                {{ $business->currentStatus()->label() }}
            </flux:badge>
        @endif
    </header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-medium text-black/60">{{ __('Phone numbers') }}</h2>

                <ul class="mt-3 flex flex-col gap-2">
                    @foreach ($business->phones as $phone)
                        <li class="flex items-center justify-between rounded-lg bg-black/4 px-4 py-3">
                            <span class="font-medium">{{ $phone->number }}</span>
                            @if ($phone->is_primary)
                                <span class="rounded-full bg-black/8 px-2.5 py-1 text-xs font-medium">{{ __('Primary') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-medium text-black/60">{{ __('Details') }}</h2>

                <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-black/40">{{ __('Address') }}</dt>
                        <dd class="mt-0.5 text-sm">{{ $business->address ?: __('—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-black/40">{{ __('Owner') }}</dt>
                        <dd class="mt-0.5 text-sm">{{ $business->owner_name ?: __('—') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-black/40">{{ __('Website / social link') }}</dt>
                        <dd class="mt-0.5 text-sm">
                            @if ($business->website)
                                <a href="{{ $business->website }}" target="_blank" rel="noopener" class="underline">{{ $business->website }}</a>
                            @else
                                {{ __('—') }}
                            @endif
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-black/40">{{ __('Notes') }}</dt>
                        <dd class="mt-0.5 text-sm whitespace-pre-line">{{ $business->notes ?: __('—') }}</dd>
                    </div>
                </dl>
            </section>

            @if ($business->screenshots->isNotEmpty())
                <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                    <h2 class="text-sm font-medium text-black/60">{{ __('Screenshots') }}</h2>

                    <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4">
                        @foreach ($business->screenshots as $screenshot)
                            <a
                                href="{{ route('calling.screenshots.show', $screenshot) }}"
                                target="_blank"
                                rel="noopener"
                                class="aspect-square overflow-hidden rounded-lg border border-zinc-200"
                            >
                                <img
                                    src="{{ route('calling.screenshots.show', $screenshot) }}"
                                    alt=""
                                    class="size-full object-cover transition-transform hover:scale-105"
                                />
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="rounded-2xl border border-zinc-200 bg-white p-5">
                <h2 class="text-sm font-medium text-black/60">{{ __('Call history') }}</h2>

                @if ($this->callHistory->isEmpty())
                    <p class="mt-3 text-sm text-black/45">{{ __('No calls logged yet.') }}</p>
                @else
                    <ul class="mt-3 flex flex-col gap-3">
                        @foreach ($this->callHistory as $call)
                            <li wire:key="call-{{ $call->id }}" class="rounded-xl bg-black/4 p-4">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <flux:badge size="sm" :color="$call->outcome->badgeColor()">
                                            {{ $call->outcome->label() }}
                                        </flux:badge>
                                        <span class="text-xs text-black/45">
                                            {{ __('by :name', ['name' => $call->user?->name ?? __('Unknown')]) }}
                                        </span>
                                    </div>
                                    <span class="text-xs text-black/40">{{ $call->called_at->format('j M Y, g:ia') }}</span>
                                </div>

                                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-black/50">
                                    @if ($call->phone)
                                        <span>{{ __('Called :number', ['number' => $call->phone->number]) }}</span>
                                    @endif

                                    @if ($call->follow_up_at)
                                        <span>{{ __('Follow up on :date', ['date' => $call->follow_up_at->format('j M Y')]) }}</span>
                                    @endif
                                </div>

                                @if ($call->note)
                                    <p class="mt-2 text-sm text-black/70">&ldquo;{{ $call->note }}&rdquo;</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="flex flex-col gap-3">
            @if ($business->phones->isNotEmpty())
                <button
                    type="button"
                    wire:click="startCall({{ $business->id }})"
                    data-test="call-button"
                    class="flex items-center justify-center gap-2 rounded-xl px-5 py-4 text-base font-medium transition-opacity hover:opacity-85"
                    style="background:var(--brand);color:var(--brand-foreground)"
                >
                    <flux:icon icon="phone" class="size-5" />
                    {{ __('Call') }}
                </button>

                @foreach ($business->phones as $phone)
                    <a
                        href="{{ $phone->telLink() }}"
                        class="flex items-center justify-between rounded-xl bg-black/4 px-4 py-3 text-sm transition-colors hover:bg-black/6"
                    >
                        <span class="font-medium">{{ $phone->number }}</span>
                        @if ($phone->is_primary)
                            <span class="text-xs text-black/40">{{ __('primary') }}</span>
                        @endif
                    </a>
                @endforeach
            @endif

            @if ($business->hasBeenAnswered())
                <flux:dropdown position="bottom" align="start" class="w-full">
                    <button
                        type="button"
                        class="w-full rounded-xl bg-black/4 px-4 py-3 text-sm font-medium transition-colors hover:bg-black/6"
                        data-test="change-status"
                    >{{ __('Change status') }}</button>

                    <flux:menu>
                        @foreach (\App\Enums\Calling\CallOutcome::answeredOptions() as $option)
                            <flux:menu.item
                                wire:click="changeStatus({{ $business->id }}, '{{ $option['value'] }}')"
                                :disabled="$business->currentStatus()->value === $option['value']"
                            >{{ $option['label'] }}</flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>
    </div>

    {{-- Phone picker (only shown when the business has more than one number) --}}
    <flux:modal name="call-phone-picker" class="w-full max-w-sm">
        <div class="flex flex-col gap-4">
            <flux:heading size="lg">{{ __('Which number?') }}</flux:heading>

            <div class="flex flex-col gap-2">
                @foreach ($business->phones as $phone)
                    <button
                        type="button"
                        wire:click="chooseCallPhone({{ $phone->id }})"
                        class="flex items-center justify-between rounded-xl bg-black/4 px-4 py-3.5 text-start transition-colors hover:bg-black/6"
                        data-test="choose-phone-{{ $phone->id }}"
                    >
                        <span class="font-medium">{{ $phone->number }}</span>
                        @if ($phone->is_primary)
                            <span class="text-xs text-black/40">{{ __('primary') }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <flux:modal.close>
                <flux:button variant="ghost" type="button" class="w-full">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
        </div>
    </flux:modal>

    {{-- Call outcome --}}
    <flux:modal name="call-outcome" class="w-full max-w-sm" @close="callStage = null">
        <div class="flex flex-col gap-5">
            @if ($callStage !== 'answered')
                <div>
                    <flux:heading size="lg">{{ __('How did it go?') }}</flux:heading>
                </div>

                <div class="flex flex-col gap-3">
                    <button
                        type="button"
                        wire:click="logNotAnswered"
                        data-test="outcome-not-answered"
                        class="rounded-xl bg-black/4 px-5 py-4 text-base font-medium transition-colors hover:bg-black/6"
                    >{{ __('Not answered') }}</button>

                    <button
                        type="button"
                        wire:click="markAnswered"
                        data-test="outcome-answered"
                        class="rounded-xl px-5 py-4 text-base font-medium transition-opacity hover:opacity-85"
                        style="background:var(--brand);color:var(--brand-foreground)"
                    >{{ __('Answered') }}</button>
                </div>
            @else
                <form wire:submit="logAnswered" class="flex flex-col gap-5">
                    <div>
                        <flux:heading size="lg">{{ __('What did they say?') }}</flux:heading>
                    </div>

                    <div class="grid grid-cols-3 gap-2">
                        @foreach (\App\Enums\Calling\CallOutcome::answeredOptions() as $option)
                            <button
                                type="button"
                                wire:click="$set('callOutcome', '{{ $option['value'] }}')"
                                data-test="select-outcome-{{ $option['value'] }}"
                                @class([
                                    'rounded-xl px-3 py-3.5 text-sm font-medium transition-colors',
                                    'bg-black text-white' => $callOutcome === $option['value'],
                                    'bg-black/4 hover:bg-black/6' => $callOutcome !== $option['value'],
                                ])
                            >{{ $option['label'] }}</button>
                        @endforeach
                    </div>

                    <flux:error name="callOutcome" />

                    <flux:textarea
                        wire:model="callNote"
                        :label="__('Note (optional)')"
                        rows="3"
                        data-test="call-note"
                    />

                    <flux:input
                        wire:model="callFollowUpAt"
                        type="date"
                        :label="__('Follow-up date (optional)')"
                        data-test="call-follow-up"
                    />

                    <button
                        type="submit"
                        data-test="save-call-outcome"
                        class="rounded-xl px-5 py-3.5 text-sm font-semibold transition-opacity hover:opacity-85 disabled:opacity-50"
                        style="background:var(--brand);color:var(--brand-foreground)"
                        wire:loading.attr="disabled"
                        wire:target="logAnswered"
                    >
                        <span wire:loading.remove wire:target="logAnswered">{{ __('Save') }}</span>
                        <span wire:loading wire:target="logAnswered">{{ __('Saving…') }}</span>
                    </button>
                </form>
            @endif
        </div>
    </flux:modal>
</div>
