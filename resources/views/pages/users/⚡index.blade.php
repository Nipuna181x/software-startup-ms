<?php

use App\Concerns\PasswordValidationRules;
use App\Enums\Role;
use App\Models\User;
use App\Policies\UserPolicy;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Users')] class extends Component {
    use PasswordValidationRules, WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public ?int $editingId = null;

    public ?int $confirmingDeleteId = null;

    public ?int $resettingPasswordId = null;

    public string $name = '';

    public string $email = '';

    public string $role = 'user';

    public bool $isActive = true;

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Authorize the whole page.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    /**
     * Get the paginated users of the current organization.
     */
    #[Computed]
    public function users(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return User::query()
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';

                $query->where(fn ($q) => $q
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(10);
    }

    /**
     * Get the assignable roles.
     *
     * @return array<int, array{value: string, label: string}>
     */
    #[Computed]
    public function roles(): array
    {
        return Role::options();
    }

    /**
     * Reset pagination whenever the search term changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Open the modal for adding a new user.
     */
    public function addUser(): void
    {
        $this->authorize('create', User::class);

        $this->resetForm();
        $this->editingId = null;

        Flux::modal('user-form')->show();
    }

    /**
     * Open the modal for editing an existing user.
     */
    public function editUser(int $userId): void
    {
        $user = $this->findInOrganization($userId);

        $this->authorize('update', $user);

        $this->resetValidation();

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->isActive = $user->is_active;
        $this->password = '';
        $this->password_confirmation = '';

        Flux::modal('user-form')->show();
    }

    /**
     * Create or update a user.
     */
    public function save(): void
    {
        $validated = $this->validate($this->rules());

        if ($this->editingId === null) {
            $this->authorize('create', User::class);

            User::create([
                'organization_id' => Auth::user()->organization_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => Role::from($validated['role']),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            Flux::toast(variant: 'success', text: __('User added.'));
        } else {
            $user = $this->findInOrganization($this->editingId);

            $this->authorize('update', $user);

            $newRole = Role::from($validated['role']);

            if ($newRole !== $user->role && ! Auth::user()->can('changeRole', [$user, $newRole])) {
                $this->addError('role', __('This organization must keep at least one Super Admin.'));

                return;
            }

            if (! $validated['isActive'] && ! Auth::user()->can('deactivate', $user)) {
                $this->addError('isActive', $this->deactivationError($user));

                return;
            }

            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $newRole,
                'is_active' => $validated['isActive'],
            ]);

            Flux::toast(variant: 'success', text: __('User updated.'));
        }

        Flux::modal('user-form')->close();
        $this->resetForm();
        unset($this->users);
    }

    /**
     * Toggle a user between active and inactive.
     */
    public function toggleActive(int $userId): void
    {
        $user = $this->findInOrganization($userId);

        if ($user->is_active) {
            if (! Auth::user()->can('deactivate', $user)) {
                Flux::toast(variant: 'danger', text: $this->deactivationError($user));

                return;
            }
        } else {
            $this->authorize('update', $user);
        }

        $user->update(['is_active' => ! $user->is_active]);

        unset($this->users);

        Flux::toast(
            variant: 'success',
            text: $user->is_active ? __('User activated.') : __('User deactivated.'),
        );
    }

    /**
     * Ask for confirmation before deleting a user.
     */
    public function confirmDelete(int $userId): void
    {
        $user = $this->findInOrganization($userId);

        if (! Auth::user()->can('delete', $user)) {
            Flux::toast(variant: 'danger', text: $this->deletionError($user));

            return;
        }

        $this->confirmingDeleteId = $user->id;

        Flux::modal('confirm-delete')->show();
    }

    /**
     * Delete the user awaiting confirmation.
     */
    public function deleteUser(): void
    {
        $user = $this->findInOrganization($this->confirmingDeleteId);

        $this->authorize('delete', $user);

        $user->delete();

        $this->confirmingDeleteId = null;

        unset($this->users);

        Flux::modal('confirm-delete')->close();
        Flux::toast(variant: 'success', text: __('User deleted.'));
    }

    /**
     * Open the reset password modal for a user.
     */
    public function startPasswordReset(int $userId): void
    {
        $user = $this->findInOrganization($userId);

        $this->authorize('resetPassword', $user);

        $this->resetValidation();

        $this->resettingPasswordId = $user->id;
        $this->password = '';
        $this->password_confirmation = '';

        Flux::modal('reset-password')->show();
    }

    /**
     * Set a new password for the selected user.
     */
    public function resetPassword(): void
    {
        $user = $this->findInOrganization($this->resettingPasswordId);

        $this->authorize('resetPassword', $user);

        $validated = $this->validate(['password' => $this->passwordRules()]);

        $user->update(['password' => $validated['password']]);

        $this->resettingPasswordId = null;
        $this->password = '';
        $this->password_confirmation = '';

        Flux::modal('reset-password')->close();
        Flux::toast(variant: 'success', text: __('Password reset.'));
    }

    /**
     * Fill the password fields with a strong generated password.
     */
    public function generatePassword(): void
    {
        $generated = Str::password(16);

        $this->password = $generated;
        $this->password_confirmation = $generated;
    }

    /**
     * Determine whether the given user is the organization's last Super Admin.
     */
    public function isLastSuperAdmin(User $user): bool
    {
        return app(UserPolicy::class)->isLastSuperAdmin($user);
    }

    /**
     * Get the validation rules for the user form.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'role' => ['required', Rule::enum(Role::class)],
            'isActive' => ['boolean'],
            'password' => $this->editingId === null
                ? $this->passwordRules()
                : ['nullable'],
        ];
    }

    /**
     * Find a user inside the current organization.
     *
     * The global scope means a user from another organization is simply not
     * found, which surfaces as a 404 rather than leaking its existence.
     */
    protected function findInOrganization(?int $userId): User
    {
        return User::query()->findOrFail($userId);
    }

    /**
     * Explain why a user may not be deactivated.
     */
    protected function deactivationError(User $user): string
    {
        return Auth::user()->is($user)
            ? __('You cannot deactivate your own account.')
            : __('This organization must keep at least one Super Admin.');
    }

    /**
     * Explain why a user may not be deleted.
     */
    protected function deletionError(User $user): string
    {
        return Auth::user()->is($user)
            ? __('You cannot delete your own account.')
            : __('This organization must keep at least one Super Admin.');
    }

    /**
     * Clear the form state.
     */
    protected function resetForm(): void
    {
        $this->reset(['name', 'email', 'role', 'isActive', 'password', 'password_confirmation']);
        $this->resetValidation();
    }
}; ?>

<div class="flex w-full flex-col gap-6">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('Users') }}</h1>
            <p class="mt-1 text-sm text-zinc-500">
                {{ __('Everyone with access to :organization.', ['organization' => $organization?->name]) }}
            </p>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="addUser" data-test="add-user">
            {{ __('Add user') }}
        </flux:button>
    </header>

    <flux:input
        wire:model.live.debounce.300ms="search"
        icon="magnifying-glass"
        :placeholder="__('Search by name or email')"
        class="max-w-sm"
        data-test="user-search"
    />

    <div class="overflow-x-auto rounded-lg border border-zinc-200">
        <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column>{{ __('Name') }}</flux:table.column>
                <flux:table.column>{{ __('Role') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Added') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->users as $user)
                    <flux:table.row :key="$user->id" wire:key="user-{{ $user->id }}">
                        <flux:table.cell>
                            <div class="flex items-center gap-3">
                                <span
                                    class="grid size-8 shrink-0 place-items-center rounded-full text-xs font-semibold"
                                    style="background:var(--brand-subtle);color:var(--brand-hover)"
                                >{{ $user->initials() }}</span>

                                <div class="min-w-0">
                                    <div class="truncate font-medium text-zinc-800">
                                        {{ $user->name }}
                                        @if ($user->is(auth()->user()))
                                            <span class="ms-1 text-xs font-normal text-zinc-400">{{ __('(you)') }}</span>
                                        @endif
                                    </div>
                                    <div class="truncate text-xs text-zinc-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" :color="$user->isSuperAdmin() ? 'blue' : 'zinc'">
                                {{ $user->role->label() }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <flux:badge size="sm" :color="$user->is_active ? 'green' : 'zinc'">
                                {{ $user->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell class="text-sm whitespace-nowrap text-zinc-500">
                            {{ $user->created_at?->format('j M Y') }}
                        </flux:table.cell>

                        <flux:table.cell class="text-end">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    icon="ellipsis-horizontal"
                                    :aria-label="__('Actions for :name', ['name' => $user->name])"
                                    data-test="user-actions-{{ $user->id }}"
                                />

                                <flux:menu>
                                    <flux:menu.item
                                        icon="pencil-square"
                                        wire:click="editUser({{ $user->id }})"
                                    >{{ __('Edit') }}</flux:menu.item>

                                    <flux:menu.item
                                        icon="key"
                                        wire:click="startPasswordReset({{ $user->id }})"
                                    >{{ __('Reset password') }}</flux:menu.item>

                                    @unless ($user->is(auth()->user()) || ($user->is_active && $this->isLastSuperAdmin($user)))
                                        <flux:menu.separator />

                                        <flux:menu.item
                                            :icon="$user->is_active ? 'pause-circle' : 'play-circle'"
                                            wire:click="toggleActive({{ $user->id }})"
                                        >{{ $user->is_active ? __('Deactivate') : __('Activate') }}</flux:menu.item>

                                        <flux:menu.item
                                            icon="trash"
                                            variant="danger"
                                            wire:click="confirmDelete({{ $user->id }})"
                                        >{{ __('Delete') }}</flux:menu.item>
                                    @endunless
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-10 text-center text-sm text-zinc-500">
                            {{ __('No users match that search.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>

    {{-- Add / edit modal --}}
    <flux:modal name="user-form" class="w-full max-w-md">
        <form wire:submit="save" class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Edit user') : __('Add user') }}
                </flux:heading>
                <flux:text class="mt-1">
                    {{ $editingId
                        ? __('Update this person\'s details and access.')
                        : __('The account is ready to use straight away.') }}
                </flux:text>
            </div>

            <flux:input wire:model="name" :label="__('Full name')" required data-test="form-name" />

            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                data-test="form-email"
            />

            <flux:select wire:model="role" :label="__('Role')" data-test="form-role">
                @foreach ($this->roles as $roleOption)
                    <flux:select.option value="{{ $roleOption['value'] }}">
                        {{ $roleOption['label'] }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:error name="role" />

            @if ($editingId)
                <flux:switch
                    wire:model="isActive"
                    :label="__('Active')"
                    :description="__('Inactive users cannot log in.')"
                    data-test="form-active"
                />

                <flux:error name="isActive" />
            @else
                <div class="flex flex-col gap-3">
                    <flux:input
                        wire:model="password"
                        :label="__('Password')"
                        type="password"
                        viewable
                        required
                        data-test="form-password"
                    />

                    <flux:input
                        wire:model="password_confirmation"
                        :label="__('Confirm password')"
                        type="password"
                        viewable
                        required
                        data-test="form-password-confirmation"
                    />

                    <flux:button
                        type="button"
                        variant="subtle"
                        size="sm"
                        icon="sparkles"
                        class="self-start"
                        wire:click="generatePassword"
                        data-test="generate-password"
                    >{{ __('Generate a password') }}</flux:button>
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="save-user">
                    <span wire:loading.remove wire:target="save">
                        {{ $editingId ? __('Save changes') : __('Add user') }}
                    </span>
                    <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Reset password modal --}}
    <flux:modal name="reset-password" class="w-full max-w-md">
        <form wire:submit="resetPassword" class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">{{ __('Reset password') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('Set a new password and share it with this person.') }}
                </flux:text>
            </div>

            <flux:input
                wire:model="password"
                :label="__('New password')"
                type="password"
                viewable
                required
                data-test="reset-password-field"
            />

            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm new password')"
                type="password"
                viewable
                required
                data-test="reset-password-confirmation"
            />

            <flux:button
                type="button"
                variant="subtle"
                size="sm"
                icon="sparkles"
                class="self-start"
                wire:click="generatePassword"
            >{{ __('Generate a password') }}</flux:button>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="confirm-reset-password">
                    <span wire:loading.remove wire:target="resetPassword">{{ __('Reset password') }}</span>
                    <span wire:loading wire:target="resetPassword">{{ __('Resetting…') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Delete confirmation --}}
    <flux:modal name="confirm-delete" class="w-full max-w-md">
        <div class="flex flex-col gap-5">
            <div>
                <flux:heading size="lg">{{ __('Delete this user?') }}</flux:heading>
                <flux:text class="mt-1">
                    {{ __('Their account and access are removed immediately. This cannot be undone.') }}
                </flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button
                    variant="danger"
                    wire:click="deleteUser"
                    data-test="confirm-delete-user"
                >
                    <span wire:loading.remove wire:target="deleteUser">{{ __('Delete user') }}</span>
                    <span wire:loading wire:target="deleteUser">{{ __('Deleting…') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
