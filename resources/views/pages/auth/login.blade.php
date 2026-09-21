<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-6">
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
                    <flux:link class="absolute end-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot password?') }}
                    </flux:link>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                {{ __('Log in') }}
            </flux:button>
        </form>

        <p class="border-t border-[color:var(--color-rule)] pt-6 text-sm text-zinc-500">
            {{ __('Registering a new company?') }}
            <flux:link :href="route('register')" wire:navigate data-test="register-link">
                {{ __('Create your workspace') }}
            </flux:link>
        </p>
    </div>
</x-layouts::auth>
