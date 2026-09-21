<?php

use App\Models\Calling\Business;
use App\Models\Calling\CallBusinessType;
use App\Models\Calling\CallCategory;
use Livewire\Component;

new class extends Component {
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
}; ?>

<div class="flex w-full flex-col gap-6">
    <x-breadcrumbs :trail="[
        ['label' => __('Calling'), 'route' => route('calling.categories.index')],
        ['label' => $category->name, 'route' => route('calling.types.index', $category)],
        ['label' => $type->name, 'route' => route('calling.businesses.index', [$category, $type])],
        ['label' => $business->name, 'route' => null],
    ]" />

    <header>
        <h1 class="display text-[30px]">{{ $business->name }}</h1>
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
        </div>

        <div class="flex flex-col gap-3">
            @foreach ($business->phones as $phone)
                <a
                    href="{{ $phone->telLink() }}"
                    class="flex items-center justify-center gap-2 rounded-xl px-5 py-4 text-base font-medium transition-opacity hover:opacity-85"
                    style="background:var(--brand);color:var(--brand-foreground)"
                    data-test="call-button-{{ $phone->id }}"
                >
                    <flux:icon icon="phone" class="size-5" />
                    {{ __('Call :number', ['number' => $phone->number]) }}
                    @if ($phone->is_primary)
                        <span class="text-xs opacity-75">({{ __('primary') }})</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>
</div>
