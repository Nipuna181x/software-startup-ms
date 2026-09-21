<?php

use App\Models\Calling\CallCategory;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Calling')] class extends Component {
    public ?int $editingId = null;

    public ?int $confirmingDeleteId = null;

    public string $name = '';

    /**
     * Authorize the whole page.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', CallCategory::class);
    }

    /**
     * Get the organization's categories with counts of types and businesses.
     */
    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return CallCategory::query()
            ->withCount('businessTypes')
            ->orderBy('name')
            ->get();
    }

    /**
     * Open the modal for adding a new category.
     */
    public function addCategory(): void
    {
        $this->authorize('create', CallCategory::class);

        $this->resetForm();
        $this->editingId = null;

        Flux::modal('category-form')->show();
    }

    /**
     * Open the modal for editing an existing category.
     */
    public function editCategory(int $categoryId): void
    {
        $category = CallCategory::query()->findOrFail($categoryId);

        $this->authorize('update', $category);

        $this->resetValidation();

        $this->editingId = $category->id;
        $this->name = $category->name;

        Flux::modal('category-form')->show();
    }

    /**
     * Create or update a category.
     */
    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        if ($this->editingId === null) {
            $this->authorize('create', CallCategory::class);

            CallCategory::create([
                'organization_id' => Auth::user()->organization_id,
                'name' => $validated['name'],
            ]);

            Flux::toast(variant: 'success', text: __('Category added.'));
        } else {
            $category = CallCategory::query()->findOrFail($this->editingId);

            $this->authorize('update', $category);

            $category->update(['name' => $validated['name']]);

            Flux::toast(variant: 'success', text: __('Category updated.'));
        }

        Flux::modal('category-form')->close();
        $this->resetForm();
        unset($this->categories);
    }

    /**
     * Ask for confirmation before deleting a category.
     */
    public function confirmDelete(int $categoryId): void
    {
        $category = CallCategory::query()->findOrFail($categoryId);

        $this->authorize('update', $category);

        if ($category->businessTypes()->exists()) {
            Flux::toast(
                variant: 'danger',
                text: __('This category still has business types inside it. Remove them first.'),
            );

            return;
        }

        $this->confirmingDeleteId = $category->id;

        Flux::modal('confirm-delete')->show();
    }

    /**
     * Delete the category awaiting confirmation.
     */
    public function deleteCategory(): void
    {
        $category = CallCategory::query()->findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $category);

        $category->delete();

        $this->confirmingDeleteId = null;

        unset($this->categories);

        Flux::modal('confirm-delete')->close();
        Flux::toast(variant: 'success', text: __('Category deleted.'));
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
    <x-breadcrumbs :trail="[['label' => __('Calling'), 'route' => null]]" />

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="display text-[30px]">{{ __('Calling') }}</h1>
            <p class="mt-2 text-sm text-black/55">
                {{ __('Organize outbound calls by category and business type.') }}
            </p>
        </div>

        @can('create', \App\Models\Calling\CallCategory::class)
            <button
                type="button"
                wire:click="addCategory"
                data-test="add-category"
                class="rounded-lg px-5 py-2.5 text-sm font-medium transition-opacity hover:opacity-85"
                style="background:var(--brand);color:var(--brand-foreground)"
            >{{ __('Add category') }}</button>
        @endcan
    </header>

    @if ($this->categories->isEmpty())
        <div class="rounded-2xl border border-zinc-200 bg-white p-10 text-center">
            <p class="text-sm text-black/55">{{ __('No categories yet.') }}</p>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->categories as $category)
                <div
                    wire:key="category-{{ $category->id }}"
                    class="group relative flex flex-col rounded-2xl border border-zinc-200 bg-white p-5 transition-colors hover:border-zinc-300"
                >
                    <a
                        href="{{ route('calling.types.index', $category) }}"
                        wire:navigate
                        class="flex-1"
                    >
                        <h2 class="font-medium text-black">{{ $category->name }}</h2>
                        <div class="mt-3 flex gap-4 text-sm text-black/50">
                            <span>{{ trans_choice(':count type|:count types', $category->business_types_count, ['count' => $category->business_types_count]) }}</span>
                        </div>
                    </a>

                    @can('update', $category)
                        <div class="mt-4 flex items-center gap-2 border-t border-zinc-100 pt-4">
                            <button
                                type="button"
                                wire:click="editCategory({{ $category->id }})"
                                data-test="edit-category-{{ $category->id }}"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium text-black/60 transition-colors hover:bg-black/5 hover:text-black"
                            >{{ __('Edit') }}</button>

                            @can('delete', $category)
                                <button
                                    type="button"
                                    wire:click="confirmDelete({{ $category->id }})"
                                    data-test="delete-category-{{ $category->id }}"
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
    <flux:modal name="category-form" class="w-full max-w-md">
        <form wire:submit="save" class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Edit category') : __('Add category') }}
                </flux:heading>
                <flux:text class="mt-1">
                    {{ __('A top-level grouping, for example "Marketing Agencies".') }}
                </flux:text>
            </div>

            <flux:input wire:model="name" :label="__('Name')" required autofocus data-test="form-name" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <button
                    type="submit"
                    data-test="save-category"
                    class="rounded-lg px-5 py-2.5 text-sm font-medium transition-opacity hover:opacity-85 disabled:opacity-50"
                    style="background:var(--brand);color:var(--brand-foreground)"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">
                        {{ $editingId ? __('Save changes') : __('Add category') }}
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
                <flux:heading size="lg">{{ __('Delete this category?') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('This cannot be undone.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteCategory" data-test="confirm-delete-category">
                    <span wire:loading.remove wire:target="deleteCategory">{{ __('Delete category') }}</span>
                    <span wire:loading wire:target="deleteCategory">{{ __('Deleting…') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
