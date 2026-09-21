<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-7">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <button
                type="submit"
                data-test="email-password-reset-link-button"
                class="button-lime w-full"
            >{{ __('Email password reset link') }}</button>
        </form>

        <p class="border-t border-black/6 pt-6 text-sm text-black/55">
            {{ __('Or, return to') }}
            <a
                href="{{ route('login') }}"
                wire:navigate
                class="font-medium text-black underline decoration-black/25 underline-offset-4 transition-colors hover:decoration-black"
            >{{ __('log in') }}</a>
        </p>
    </div>
</x-layouts::auth>
