<?php

use App\Models\Calling\CallBusinessType;
use App\Models\Calling\CallCategory;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public CallCategory $category;

    public ?int $editingId = null;

    public ?int $confirmingDeleteId = null;

    public string $name = '';

    /**
     * Authorize the whole page and bind the route model.
     */
    public function mount(CallCategory $category): void
    {
        $this->authorize('view', $category);

        $this->category = $category;
    }

    /**
     * Render the component with a dynamic page title.
     */
    public function render(): \Illuminate\Contracts\View\View
    {
        return $this->view()->title($this->category->name);
    }

    /**
     * Get this category's business types.
     */
    #[Computed]
    public function types(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->category->businessTypes()
            ->withCount('businesses')
            ->orderBy('name')
            ->get();
    }

    /**
     * Open the modal for adding a new business type.
     */
    public function addType(): void
    {
        $this->authorize('create', CallBusinessType::class);

        $this->resetForm();
        $this->editingId = null;

        Flux::modal('type-form')->show();
    }

    /**
     * Open the modal for editing an existing business type.
     */
    public function editType(int $typeId): void
    {
        $type = $this->category->businessTypes()->findOrFail($typeId);

        $this->authorize('update', $type);

        $this->resetValidation();

        $this->editingId = $type->id;
        $this->name = $type->name;

        Flux::modal('type-form')->show();
    }

    /**
     * Create or update a business type.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        if ($this->editingId === null) {
            $this->authorize('create', CallBusinessType::class);

            CallBusinessType::create([
                'organization_id' => Auth::user()->organization_id,
                'call_category_id' => $this->category->id,
                'name' => $validated['name'],
            ]);

            Flux::toast(variant: 'success', text: __('Business type added.'));
        } else {
            $type = $this->category->businessTypes()->findOrFail($this->editingId);

            $this->authorize('update', $type);

            $type->update(['name' => $validated['name']]);

            Flux::toast(variant: 'success', text: __('Business type updated.'));
        }

        Flux::modal('type-form')->close();
        $this->resetForm();
        unset($this->types);
    }

    /**
     * Ask for confirmation before deleting a business type.
     */
    public function confirmDelete(int $typeId): void
    {
        $type = $this->category->businessTypes()->findOrFail($typeId);

        $this->authorize('update', $type);

        if ($type->businesses()->exists()) {
            Flux::toast(
                variant: 'danger',
                text: __('This business type still has businesses inside it. Remove them first.'),
            );

            return;
        }

        $this->confirmingDeleteId = $type->id;

        Flux::modal('confirm-delete')->show();
    }

    /**
     * Delete the business type awaiting confirmation.
     */
    public function deleteType(): void
    {
        $type = $this->category->businessTypes()->findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $type);

        $type->delete();

        $this->confirmingDeleteId = null;

        unset($this->types);

        Flux::modal('confirm-delete')->close();
        Flux::toast(variant: 'success', text: __('Business type deleted.'));
    }

    /**
     * Clear the form state.
     */
    protected function resetForm(): void
    {
        $this->reset(['name']);
        $this->resetValidation();
    }
}; ?>

<div class="flex w-full flex-col gap-6">
    <x-breadcrumbs :trail="[
        ['label' => __('Calling'), 'route' => route('calling.categories.index')],
        ['label' => $category->name, 'route' => null],
    ]" />

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="display text-[30px]">{{ $category->name }}</h1>
            <p class="mt-2 text-sm text-black/55">
                {{ __('Business types in this category.') }}
            </p>
        </div>

        @can('create', \App\Models\Calling\CallBusinessType::class)
            <button
                type="button"
                wire:click="addType"
                data-test="add-type"
                class="rounded-lg px-5 py-2.5 text-sm font-medium transition-opacity hover:opacity-85"
                style="background:var(--brand);color:var(--brand-foreground)"
            >{{ __('Add business type') }}</button>
        @endcan
    </header>

    @if ($this->types->isEmpty())
        <div class="rounded-2xl border border-zinc-200 bg-white p-10 text-center">
            <p class="text-sm text-black/55">{{ __('No business types yet.') }}</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->types as $type)
                <div
                    wire:key="type-{{ $type->id }}"
                    class="group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-5 transition-colors hover:border-zinc-300"
                >
                    <a
                        href="{{ route('calling.businesses.index', [$category, $type]) }}"
                        wire:navigate
                        class="flex-1"
                    >
                        <h2 class="font-medium text-black">{{ $type->name }}</h2>
                        <p class="mt-3 text-sm text-black/50">
                            {{ trans_choice(':count business|:count businesses', $type->businesses_count, ['count' => $type->businesses_count]) }}
                        </p>
                    </a>

                    @can('update', $type)
                        <div class="mt-4 flex items-center gap-2 border-t border-zinc-100 pt-4">
                            <button
                                type="button"
                                wire:click="editType({{ $type->id }})"
                                data-test="edit-type-{{ $type->id }}"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium text-black/60 transition-colors hover:bg-black/5 hover:text-black"
                            >{{ __('Edit') }}</button>

                            @can('delete', $type)
                                <button
                                    type="button"
                                    wire:click="confirmDelete({{ $type->id }})"
                                    data-test="delete-type-{{ $type->id }}"
                                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50"
                                >{{ __('Delete') }}</button>
                            @else
                                <span class="text-xs text-black/35">{{ __('Not empty') }}</span>
                            @endcan
                        </div>
                    @endcan
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add / edit modal --}}
    <flux:modal name="type-form" class="w-full max-w-md">
        <form wire:submit="save" class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Edit business type') : __('Add business type') }}
                </flux:heading>
                <flux:text class="mt-1">
                    {{ __('For example "Phone Shops" or "Cushion Shops".') }}
                </flux:text>
            </div>

            <flux:input wire:model="name" :label="__('Name')" required autofocus data-test="form-name" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <button
                    type="submit"
                    data-test="save-type"
                    class="rounded-lg px-5 py-2.5 text-sm font-medium transition-opacity hover:opacity-85 disabled:opacity-50"
                    style="background:var(--brand);color:var(--brand-foreground)"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">
                        {{ $editingId ? __('Save changes') : __('Add business type') }}
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
                <flux:heading size="lg">{{ __('Delete this business type?') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('This cannot be undone.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteType" data-test="confirm-delete-type">
                    <span wire:loading.remove wire:target="deleteType">{{ __('Delete business type') }}</span>
                    <span wire:loading wire:target="deleteType">{{ __('Deleting…') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
