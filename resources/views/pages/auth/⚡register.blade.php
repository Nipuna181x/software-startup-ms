<?php

use App\Actions\Organizations\RegisterOrganization;
use App\Concerns\PasswordValidationRules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::auth', ['wide' => true])] #[Title('Register your company')] class extends Component {
    use PasswordValidationRules, WithFileUploads;

    public string $organizationName = '';

    public $logo = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Get the company name shown in the preview.
     */
    #[Computed]
    public function previewName(): string
    {
        return trim($this->organizationName) !== ''
            ? trim($this->organizationName)
            : __('Your company');
    }

    /**
     * Validate a field as soon as the user leaves it.
     */
    public function updated(string $property): void
    {
        $this->validateOnly($property, $this->rules());
    }

    /**
     * Register the organization and log the new Super Admin in.
     */
    public function register(RegisterOrganization $registerOrganization): void
    {
        $this->ensureIsNotRateLimited();

        $validated = $this->validate($this->rules());

        $logoPath = $this->logo?->store(
            config('startsuite.logo.directory'),
            config('startsuite.logo.disk'),
        );

        $user = $registerOrganization->handle(
            organizationAttributes: [
                'name' => $validated['organizationName'],
                'logo_path' => $logoPath ?: null,
            ],
            adminAttributes: [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ],
        );

        RateLimiter::increment($this->throttleKey());

        Auth::login($user);

        session()->regenerate();

        $this->redirectIntended(route('dashboard', absolute: false), navigate: false);
    }

    /**
     * Get the validation rules for the whole form.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'organizationName' => ['required', 'string', 'min:2', 'max:255'],
            'logo' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('startsuite.logo.mimes')),
                'max:'.config('startsuite.logo.max_kilobytes'),
            ],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => $this->passwordRules(),
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
            'organizationName.required' => __('Enter your company name.'),
            'logo.mimes' => __('The logo must be a PNG, JPG or WEBP file. SVG files are not accepted.'),
            'logo.max' => __('The logo may not be larger than 2 MB.'),
            'email.unique' => __('That email address already has a Startsuite account.'),
        ];
    }

    /**
     * Block repeated registration attempts from the same address.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            return;
        }

        throw ValidationException::withMessages([
            'organizationName' => __('Too many registrations from this device. Try again in :seconds seconds.', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
            ]),
        ]);
    }

    /**
     * Get the rate limiter key for the current visitor.
     */
    protected function throttleKey(): string
    {
        return 'organization-registration|'.request()->ip();
    }
}; ?>

<div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
    {{-- Form --}}
    <div class="lg:col-span-7">
        <x-auth-header
            :title="__('Register your company')"
            :description="__('Create your workspace and your own Super Admin account. This takes about two minutes.')"
        />

        <form wire:submit="register" class="mt-9 flex flex-col gap-10">
            {{-- Organization --}}
            <fieldset class="flex flex-col gap-5">
                <legend class="mb-5 w-full text-xs font-medium tracking-[0.14em] text-black/35 uppercase">
                    {{ __('Your company') }}
                </legend>

                <flux:input
                    wire:model.blur="organizationName"
                    :label="__('Company name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="organization"
                    placeholder="Northwind Logistics"
                    data-test="organization-name"
                />

                <flux:field>
                    <flux:label badge="{{ __('Optional') }}">{{ __('Logo') }}</flux:label>

                    <input
                        type="file"
                        wire:model="logo"
                        accept="image/png,image/jpeg,image/webp"
                        data-test="logo-input"
                        class="block w-full cursor-pointer rounded-xl bg-black/4 text-sm text-black/60 transition-colors hover:bg-black/6 file:mr-4 file:cursor-pointer file:rounded-l-xl file:border-0 file:bg-black/6 file:px-4 file:py-3 file:text-sm file:font-medium file:text-black"
                    />

                    <flux:description>
                        {{ __('PNG, JPG or WEBP, up to 2 MB. SVG files are not accepted.') }}
                    </flux:description>

                    <div wire:loading wire:target="logo" class="text-sm text-black/50">
                        {{ __('Uploading…') }}
                    </div>

                    <flux:error name="logo" />
                </flux:field>

            </fieldset>

            {{-- Admin account --}}
            <fieldset class="flex flex-col gap-5">
                <legend class="mb-5 w-full text-xs font-medium tracking-[0.14em] text-black/35 uppercase">
                    {{ __('Your Super Admin account') }}
                </legend>

                <flux:input
                    wire:model.blur="name"
                    :label="__('Full name')"
                    type="text"
                    required
                    autocomplete="name"
                    placeholder="Amara Osei"
                    data-test="admin-name"
                />

                <flux:input
                    wire:model.blur="email"
                    :label="__('Email address')"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="you@company.com"
                    data-test="admin-email"
                />

                <flux:input
                    wire:model="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                    data-test="admin-password"
                />

                <flux:input
                    wire:model="password_confirmation"
                    :label="__('Confirm password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    viewable
                    data-test="admin-password-confirmation"
                />
            </fieldset>

            <div class="flex flex-col gap-4">
                <button
                    type="submit"
                    data-test="register-button"
                    class="button-lime w-full disabled:opacity-50"
                    wire:loading.attr="disabled"
                    wire:target="register"
                >
                    <span wire:loading.remove wire:target="register">{{ __('Create workspace') }}</span>
                    <span wire:loading wire:target="register">{{ __('Creating your workspace…') }}</span>
                </button>

                <p class="text-sm text-black/55">
                    {{ __('Already have an account?') }}
                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="font-medium text-black underline decoration-black/25 underline-offset-4 transition-colors hover:decoration-black"
                    >{{ __('Log in') }}</a>
                </p>
            </div>
        </form>
    </div>

    {{-- Live preview --}}
    <div class="lg:col-span-5">
        <div class="lg:sticky lg:top-24">
            <p class="text-xs font-medium tracking-[0.14em] text-black/35 uppercase">
                {{ __('Live preview') }}
            </p>

            <p class="mt-2.5 text-sm leading-relaxed text-black/55">
                {{ __('This is how your workspace sidebar will look to your team.') }}
            </p>

            <div class="mt-5 rounded-2xl bg-black/4 p-2">
                <x-organization-preview
                    :name="$this->previewName"
                    :logo-url="$logo?->temporaryUrl()"
                    class="!rounded-xl !border-black/8"
                />
            </div>
        </div>
    </div>
</div>
