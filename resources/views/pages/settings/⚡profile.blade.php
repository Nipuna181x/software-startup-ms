<?php

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Profile settings')] class extends Component {
    use PasswordValidationRules, ProfileValidationRules;

    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the current user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated)->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Change the current user's password.
     */
    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ]);

        Auth::user()->update(['password' => $validated['password']]);

        $this->reset(['current_password', 'password', 'password_confirmation']);

        Flux::toast(variant: 'success', text: __('Password changed.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Your name, email address and password')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autocomplete="name" />

            <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

            <button
                type="submit"
                data-test="update-profile-button"
                class="rounded-lg px-5 py-2.5 text-sm font-medium transition-opacity hover:opacity-85 disabled:opacity-50"
                style="background:var(--brand);color:var(--brand-foreground)"
                wire:loading.attr="disabled"
                wire:target="updateProfileInformation"
            >
                <span wire:loading.remove wire:target="updateProfileInformation">{{ __('Save') }}</span>
                <span wire:loading wire:target="updateProfileInformation">{{ __('Saving…') }}</span>
            </button>
        </form>

        <flux:separator class="my-8" variant="subtle" />

        <form wire:submit="updatePassword" class="w-full space-y-6">
            <h3 class="text-[15px] font-medium tracking-tight">{{ __('Change password') }}</h3>

            <flux:input
                wire:model="current_password"
                :label="__('Current password')"
                type="password"
                viewable
                required
                autocomplete="current-password"
                data-test="current-password"
            />

            <flux:input
                wire:model="password"
                :label="__('New password')"
                type="password"
                viewable
                required
                autocomplete="new-password"
                data-test="new-password"
            />

            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm new password')"
                type="password"
                viewable
                required
                autocomplete="new-password"
                data-test="new-password-confirmation"
            />

            <button
                type="submit"
                data-test="update-password-button"
                class="rounded-lg px-5 py-2.5 text-sm font-medium transition-opacity hover:opacity-85 disabled:opacity-50"
                style="background:var(--brand);color:var(--brand-foreground)"
                wire:loading.attr="disabled"
                wire:target="updatePassword"
            >
                <span wire:loading.remove wire:target="updatePassword">{{ __('Change password') }}</span>
                <span wire:loading wire:target="updatePassword">{{ __('Changing…') }}</span>
            </button>
        </form>
    </x-pages::settings.layout>
</section>
