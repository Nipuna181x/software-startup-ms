<?php

use App\Models\Calling\Business;
use App\Models\Calling\CallBusinessType;
use App\Models\Calling\CallCategory;
use App\Support\PhoneNumber;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public CallCategory $category;

    public CallBusinessType $type;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $editingId = null;

    public ?int $confirmingDeleteId = null;

    public string $name = '';

    public ?string $address = null;

    public ?string $ownerName = null;

    public ?string $website = null;

    public ?string $notes = null;

    /** @var array<int, array{id: int|null, number: string, is_primary: bool}> */
    public array $phones = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newScreenshots = [];

    /** @var array<int, int> ids of existing screenshots marked for removal */
    public array $removedScreenshotIds = [];

    /**
     * Duplicate business found for the phone number just entered, if any.
     */
    public ?Business $duplicateBusiness = null;

    /**
     * Authorize the whole page and bind the route models.
     */
    public function mount(CallCategory $category, CallBusinessType $type): void
    {
        $this->authorize('view', $type);

        $this->category = $category;
        $this->type = $type;
    }

    /**
     * Render the component with a dynamic page title.
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        return $this->view()->title($this->type->name);
    }

    /**
     * Get this business type's businesses, filtered by the search term.
     */
    #[Computed]
    public function businesses(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->type->businesses()
            ->with(['phones' => fn ($query) => $query->orderByDesc('is_primary')])
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $normalizedSearch = PhoneNumber::normalize($this->search);

                $query->where(function ($query) use ($term, $normalizedSearch): void {
                    $query->where('name', 'like', $term);

                    if ($normalizedSearch !== '') {
                        $query->orWhereHas('phones', function ($query) use ($normalizedSearch): void {
                            $query->where('normalized_number', 'like', '%'.$normalizedSearch.'%');
                        });
                    }
                });
            })
            ->latest()
            ->get();
    }

    /**
     * Open the modal for adding a new business.
     */
    public function addBusiness(): void
    {
        $this->authorize('create', Business::class);

        $this->resetForm();
        $this->editingId = null;

        Flux::modal('business-form')->show();
    }

    /**
     * Open the modal for editing an existing business.
     */
    public function editBusiness(int $businessId): void
    {
        $business = $this->type->businesses()->with('phones')->findOrFail($businessId);

        $this->authorize('update', $business);

        $this->resetValidation();
        $this->duplicateBusiness = null;

        $this->editingId = $business->id;
        $this->name = $business->name;
        $this->address = $business->address;
        $this->ownerName = $business->owner_name;
        $this->website = $business->website;
        $this->notes = $business->notes;
        $this->phones = $business->phones->map(fn ($phone) => [
            'id' => $phone->id,
            'number' => $phone->number,
            'is_primary' => $phone->is_primary,
        ])->all();
        $this->newScreenshots = [];
        $this->removedScreenshotIds = [];

        Flux::modal('business-form')->show();
    }

    /**
     * Add another empty phone number row to the form.
     */
    public function addPhoneField(): void
    {
        $this->phones[] = ['id' => null, 'number' => '', 'is_primary' => count($this->phones) === 0];
    }

    /**
     * Remove a phone number row from the form.
     */
    public function removePhoneField(int $index): void
    {
        unset($this->phones[$index]);
        $this->phones = array_values($this->phones);

        if (! empty($this->phones) && ! collect($this->phones)->contains('is_primary', true)) {
            $this->phones[0]['is_primary'] = true;
        }
    }

    /**
     * Mark a single phone number as the primary one.
     */
    public function makePrimary(int $index): void
    {
        foreach ($this->phones as $i => $phone) {
            $this->phones[$i]['is_primary'] = $i === $index;
        }
    }

    /**
     * Mark an existing screenshot for removal when the form is saved.
     */
    public function removeExistingScreenshot(int $screenshotId): void
    {
        $this->removedScreenshotIds[] = $screenshotId;
    }

    /**
     * Check whether the first phone number entered already belongs to
     * another business in the organization, and warn if so.
     */
    public function updatedPhones(): void
    {
        $this->duplicateBusiness = null;

        foreach ($this->phones as $phone) {
            if (trim($phone['number'] ?? '') === '') {
                continue;
            }

            $existing = Business::findByPhoneNumber($phone['number']);

            if ($existing !== null && $existing->id !== $this->editingId) {
                $this->duplicateBusiness = $existing;

                break;
            }
        }
    }

    /**
     * Create or update a business.
     */
    public function save(): void
    {
        $validated = $this->validate($this->rules());

        if (empty(array_filter($this->phones, fn ($p) => trim($p['number']) !== ''))) {
            $this->addError('phones', __('Add at least one phone number.'));

            return;
        }

        if ($this->editingId === null) {
            $this->authorize('create', Business::class);

            $business = Business::create([
                'organization_id' => Auth::user()->organization_id,
                'call_business_type_id' => $this->type->id,
                'name' => $validated['name'],
                'address' => $validated['address'] ?: null,
                'owner_name' => $validated['ownerName'] ?: null,
                'website' => $validated['website'] ?: null,
                'notes' => $validated['notes'] ?: null,
            ]);

            Flux::toast(variant: 'success', text: __('Business added.'));
        } else {
            $business = $this->type->businesses()->findOrFail($this->editingId);

            $this->authorize('update', $business);

            $business->update([
                'name' => $validated['name'],
                'address' => $validated['address'] ?: null,
                'owner_name' => $validated['ownerName'] ?: null,
                'website' => $validated['website'] ?: null,
                'notes' => $validated['notes'] ?: null,
            ]);

            Flux::toast(variant: 'success', text: __('Business updated.'));
        }

        $this->syncPhones($business);
        $this->syncScreenshots($business);

        Flux::modal('business-form')->close();
        $this->resetForm();
        unset($this->businesses);
    }

    /**
     * Replace the business's phone numbers with the form's current state.
     */
    protected function syncPhones(Business $business): void
    {
        $keepIds = [];

        foreach ($this->phones as $phone) {
            $number = trim($phone['number']);

            if ($number === '') {
                continue;
            }

            $record = $business->phones()->updateOrCreate(
                ['id' => $phone['id']],
                [
                    'organization_id' => $business->organization_id,
                    'number' => $number,
                    'is_primary' => (bool) $phone['is_primary'],
                ],
            );

            $keepIds[] = $record->id;
        }

        $business->phones()->whereNotIn('id', $keepIds)->delete();
    }

    /**
     * Store new screenshots and remove any marked for deletion.
     */
    protected function syncScreenshots(Business $business): void
    {
        if (! empty($this->removedScreenshotIds)) {
            $business->screenshots()
                ->whereIn('id', $this->removedScreenshotIds)
                ->get()
                ->each->delete();
        }

        foreach ($this->newScreenshots as $screenshot) {
            $path = $screenshot->store(
                config('startsuite.business_screenshot.directory'),
                config('startsuite.business_screenshot.disk'),
            );

            $business->screenshots()->create([
                'organization_id' => $business->organization_id,
                'path' => $path,
            ]);
        }
    }

    /**
     * Ask for confirmation before deleting a business.
     */
    public function confirmDelete(int $businessId): void
    {
        $business = $this->type->businesses()->findOrFail($businessId);

        $this->authorize('delete', $business);

        $this->confirmingDeleteId = $business->id;

        Flux::modal('confirm-delete')->show();
    }

    /**
     * Delete the business awaiting confirmation.
     */
    public function deleteBusiness(): void
    {
        $business = $this->type->businesses()->findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $business);

        $business->delete();

        $this->confirmingDeleteId = null;

        unset($this->businesses);

        Flux::modal('confirm-delete')->close();
        Flux::toast(variant: 'success', text: __('Business deleted.'));
    }

    /**
     * Get the validation rules for the business form.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'ownerName' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'phones.*.number' => ['required', 'string', 'max:32'],
            'newScreenshots.*' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('startsuite.business_screenshot.mimes')),
                'max:'.config('startsuite.business_screenshot.max_kilobytes'),
            ],
        ];
    }

    /**
     * Get the validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'newScreenshots.*.mimes' => __('Screenshots must be PNG, JPG or WEBP files.'),
            'newScreenshots.*.max' => __('Each screenshot may not be larger than 4 MB.'),
            'phones.*.number.required' => __('Enter a phone number or remove this field.'),
        ];
    }

    /**
     * Clear the form state.
     */
    protected function resetForm(): void
    {
        $this->reset([
            'name', 'address', 'ownerName', 'website', 'notes',
            'phones', 'newScreenshots', 'removedScreenshotIds', 'duplicateBusiness',
        ]);
        $this->phones = [['id' => null, 'number' => '', 'is_primary' => true]];
        $this->resetValidation();
    }
}; ?>

<div class="flex w-full flex-col gap-6">
    <x-breadcrumbs :trail="[
        ['label' => __('Calling'), 'route' => route('calling.categories.index')],
        ['label' => $category->name, 'route' => route('calling.types.index', $category)],
        ['label' => $type->name, 'route' => null],
    ]" />

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="display text-[30px]">{{ $type->name }}</h1>
            <p class="mt-2 text-sm text-black/55">
                {{ __('Businesses in this category.') }}
            </p>
        </div>

        @can('create', \App\Models\Calling\Business::class)
            <button
                type="button"
                wire:click="addBusiness"
                data-test="add-business"
                class="rounded-lg px-5 py-2.5 text-sm font-medium transition-opacity hover:opacity-85"
                style="background:var(--brand);color:var(--brand-foreground)"
            >{{ __('Add business') }}</button>
        @endcan
    </header>

    <flux:input
        wire:model.live.debounce.300ms="search"
        icon="magnifying-glass"
        :placeholder="__('Search by name or number')"
        class="max-w-sm"
        aria-label="Search businesses"
        data-test="business-search"
    />

    @if ($this->businesses->isEmpty())
        <div class="rounded-2xl border border-zinc-200 bg-white p-10 text-center">
            <p class="text-sm text-black/55">{{ __('No businesses yet.') }}</p>
        </div>
    @else
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
            <ul class="divide-y divide-black/5">
                @foreach ($this->businesses as $business)
                    <li wire:key="business-{{ $business->id }}" class="flex items-center gap-3 p-4 sm:p-5">
                        <div class="min-w-0 flex-1">
                            <a
                                href="{{ route('calling.businesses.show', [$category, $type, $business]) }}"
                                wire:navigate
                                class="font-medium text-black hover:underline"
                            >{{ $business->name }}</a>

                            <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-sm text-black/50">
                                @forelse ($business->phones as $phone)
                                    <span>{{ $phone->number }}@if($phone->is_primary) <span class="text-black/35">({{ __('primary') }})</span>@endif</span>
                                @empty
                                    <span class="text-black/35">{{ __('No phone number') }}</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            @can('update', $business)
                                <button
                                    type="button"
                                    wire:click="editBusiness({{ $business->id }})"
                                    data-test="edit-business-{{ $business->id }}"
                                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-black/60 transition-colors hover:bg-black/5 hover:text-black"
                                >{{ __('Edit') }}</button>
                            @endcan

                            @can('delete', $business)
                                <button
                                    type="button"
                                    wire:click="confirmDelete({{ $business->id }})"
                                    data-test="delete-business-{{ $business->id }}"
                                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50"
                                >{{ __('Delete') }}</button>
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Add / edit modal --}}
    <flux:modal name="business-form" class="w-full max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Edit business') : __('Add business') }}
                </flux:heading>
            </div>

            <flux:input wire:model="name" :label="__('Business name')" required autofocus data-test="form-name" />

            <div class="flex flex-col gap-3">
                <flux:label>{{ __('Phone numbers') }}</flux:label>

                @foreach ($phones as $index => $phone)
                    <div class="flex items-center gap-2" wire:key="phone-{{ $index }}">
                        <flux:input
                            wire:model.live.debounce.500ms="phones.{{ $index }}.number"
                            :placeholder="__('e.g. 071 234 5678')"
                            class="flex-1"
                            data-test="phone-number-{{ $index }}"
                        />

                        <button
                            type="button"
                            wire:click="makePrimary({{ $index }})"
                            title="{{ __('Set as primary') }}"
                            @class([
                                'shrink-0 rounded-lg px-3 py-2.5 text-xs font-medium transition-colors',
                                'bg-black/8 text-black' => $phone['is_primary'],
                                'text-black/40 hover:bg-black/5' => ! $phone['is_primary'],
                            ])
                            data-test="phone-primary-{{ $index }}"
                        >{{ __('Primary') }}</button>

                        @if (count($phones) > 1)
                            <button
                                type="button"
                                wire:click="removePhoneField({{ $index }})"
                                class="shrink-0 rounded-lg px-2.5 py-2.5 text-black/40 transition-colors hover:bg-red-50 hover:text-red-600"
                                aria-label="{{ __('Remove this number') }}"
                                data-test="remove-phone-{{ $index }}"
                            ><flux:icon icon="x-mark" class="size-4" /></button>
                        @endif
                    </div>

                    <flux:error name="phones.{{ $index }}.number" />
                @endforeach

                <flux:error name="phones" />

                <button
                    type="button"
                    wire:click="addPhoneField"
                    class="self-start rounded-lg px-3 py-1.5 text-xs font-medium text-black/60 transition-colors hover:bg-black/5 hover:text-black"
                    data-test="add-phone"
                >{{ __('+ Add another number') }}</button>

                @if ($duplicateBusiness)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800" data-test="duplicate-warning">
                        {{ __('This number already belongs to :name.', ['name' => $duplicateBusiness->name]) }}
                        <a
                            href="{{ route('calling.businesses.show', [$category, $type, $duplicateBusiness]) }}"
                            wire:navigate
                            class="font-medium underline"
                        >{{ __('View business') }}</a>
                    </div>
                @endif
            </div>

            <flux:input wire:model="address" :label="__('Address')" data-test="form-address" />
            <flux:input wire:model="ownerName" :label="__('Owner name')" data-test="form-owner" />
            <flux:input wire:model="website" :label="__('Website or social link')" data-test="form-website" />
            <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" data-test="form-notes" />

            <div class="flex flex-col gap-3">
                <flux:label>{{ __('Screenshots') }}</flux:label>

                @if ($editingId)
                    @php($existingScreenshots = $this->type->businesses()->find($editingId)?->screenshots ?? collect())

                    @if ($existingScreenshots->whereNotIn('id', $removedScreenshotIds)->isNotEmpty())
                        <div class="grid grid-cols-4 gap-2">
                            @foreach ($existingScreenshots->whereNotIn('id', $removedScreenshotIds) as $screenshot)
                                <div class="group relative aspect-square overflow-hidden rounded-lg border border-zinc-200" wire:key="shot-{{ $screenshot->id }}">
                                    <a href="{{ route('calling.screenshots.show', $screenshot) }}" target="_blank" rel="noopener">
                                        <img
                                            src="{{ route('calling.screenshots.show', $screenshot) }}"
                                            alt=""
                                            class="size-full object-cover"
                                        />
                                    </a>
                                    <button
                                        type="button"
                                        wire:click="removeExistingScreenshot({{ $screenshot->id }})"
                                        class="absolute end-1 top-1 rounded-full bg-black/60 p-1 text-white opacity-0 transition-opacity group-hover:opacity-100"
                                        aria-label="{{ __('Remove screenshot') }}"
                                    ><flux:icon icon="x-mark" class="size-3.5" /></button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endif

                <input
                    type="file"
                    wire:model="newScreenshots"
                    multiple
                    accept="image/png,image/jpeg,image/webp"
                    data-test="screenshot-input"
                    class="block w-full cursor-pointer rounded-lg bg-black/4 text-sm text-black/60 file:mr-4 file:cursor-pointer file:rounded-l-lg file:border-0 file:bg-black/6 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-black"
                />

                <flux:description>
                    {{ __('PNG, JPG or WEBP, up to 4 MB each.') }}
                </flux:description>

                @if (! empty($newScreenshots))
                    <div class="grid grid-cols-4 gap-2">
                        @foreach ($newScreenshots as $index => $screenshot)
                            <div class="aspect-square overflow-hidden rounded-lg border border-zinc-200" wire:key="new-shot-{{ $index }}">
                                <img src="{{ $screenshot->temporaryUrl() }}" alt="" class="size-full object-cover" />
                            </div>
                        @endforeach
                    </div>
                @endif

                <flux:error name="newScreenshots.*" />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <button
                    type="submit"
                    data-test="save-business"
                    class="rounded-lg px-5 py-2.5 text-sm font-medium transition-opacity hover:opacity-85 disabled:opacity-50"
                    style="background:var(--brand);color:var(--brand-foreground)"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">
                        {{ $editingId ? __('Save changes') : __('Add business') }}
                    </span>
                    <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
                </button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete confirmation --}}
    <flux:modal name="confirm-delete" class="w-full max-w-md">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">{{ __('Delete this business?') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('This will also delete its call history and screenshots. This cannot be undone.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteBusiness" data-test="confirm-delete-business">
                    <span wire:loading.remove wire:target="deleteBusiness">{{ __('Delete business') }}</span>
                    <span wire:loading wire:target="deleteBusiness">{{ __('Deleting…') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
