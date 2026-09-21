<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-7">
        <x-auth-header
            :title="__('Log in')"
            :description="__('Enter the email and password for your Startsuite account.')"
        />

        <x-auth-session-status :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="you@company.com"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    viewable
                />

                @if (Route::has('password.request'))
                    <a
                        class="absolute end-0 top-0 text-sm text-black/50 transition-colors hover:text-black"
                        href="{{ route('password.request') }}"
                        wire:navigate
                    >{{ __('Forgot password?') }}</a>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <button
                type="submit"
                data-test="login-button"
                class="button-lime w-full"
            >{{ __('Log in') }}</button>
        </form>

        <p class="border-t border-black/6 pt-6 text-sm text-black/55">
            {{ __('Registering a new company?') }}
            <a
                href="{{ route('register') }}"
                wire:navigate
                data-test="register-link"
                class="font-medium text-black underline decoration-black/25 underline-offset-4 transition-colors hover:decoration-black"
            >{{ __('Create your workspace') }}</a>
        </p>
    </div>
</x-layouts::auth>
