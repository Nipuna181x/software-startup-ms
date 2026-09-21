<?php

use App\Rules\ReadableThemeColor;
use App\Support\Color;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Organization settings')] class extends Component {
    use WithFileUploads;

    public string $name = '';

    public string $primaryColor = '';

    public $logo = null;

    public bool $removeLogo = false;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('update', Auth::user()->organization);

        $organization = Auth::user()->organization;

        $this->name = $organization->name;
        $this->primaryColor = $organization->primary_color;
    }

    /**
     * Get the theme swatches.
     *
     * @return array<int, array{label: string, value: string}>
     */
    #[Computed]
    public function presets(): array
    {
        return config('startsuite.theme_presets');
    }

    /**
     * Get the colour used by the preview panel.
     */
    #[Computed]
    public function previewColor(): string
    {
        return Color::isValidHex($this->primaryColor)
            ? Color::normalize($this->primaryColor)
            : config('startsuite.default_organization_color');
    }

    /**
     * Get the logo shown in the preview, honouring a pending upload or removal.
     */
    #[Computed]
    public function previewLogoUrl(): ?string
    {
        if ($this->logo) {
            return $this->logo->temporaryUrl();
        }

        if ($this->removeLogo) {
            return null;
        }

        return Auth::user()->organization->logoUrl();
    }

    /**
     * Mark the current logo for removal.
     */
    public function markLogoForRemoval(): void
    {
        $this->logo = null;
        $this->removeLogo = true;
    }

    /**
     * Save the organization's branding.
     */
    public function save(): void
    {
        $organization = Auth::user()->organization;

        $this->authorize('update', $organization);

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'primaryColor' => ['required', 'string', new ReadableThemeColor],
            'logo' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('startsuite.logo.mimes')),
                'max:'.config('startsuite.logo.max_kilobytes'),
            ],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'primary_color' => Color::normalize($validated['primaryColor']),
        ];

        if ($this->logo) {
            $this->deleteExistingLogo($organization->logo_path);

            $attributes['logo_path'] = $this->logo->store(
                config('startsuite.logo.directory'),
                config('startsuite.logo.disk'),
            );
        } elseif ($this->removeLogo) {
            $this->deleteExistingLogo($organization->logo_path);

            $attributes['logo_path'] = null;
        }

        $organization->update($attributes);

        $this->logo = null;
        $this->removeLogo = false;

        Flux::toast(variant: 'success', text: __('Organization updated.'));
    }

    /**
     * Delete a stored logo file, if there is one.
     */
    protected function deleteExistingLogo(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(config('startsuite.logo.disk'))->delete($path);
        }
    }

    /**
     * Get the validation messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'logo.mimes' => __('The logo must be a PNG, JPG or WEBP file. SVG files are not accepted.'),
            'logo.max' => __('The logo may not be larger than 2 MB.'),
        ];
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout
        :heading="__('Organization')"
        :subheading="__('Your company name, logo and theme colour')"
        :wide="true"
    >
        <div class="grid gap-8 lg:grid-cols-2">
            <form wire:submit="save" class="flex flex-col gap-6">
                <flux:input
                    wire:model.blur="name"
                    :label="__('Company name')"
                    type="text"
                    required
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

                    @if ($this->previewLogoUrl)
                        <flux:button
                            type="button"
                            variant="subtle"
                            size="sm"
                            icon="trash"
                            class="mt-2 self-start"
                            wire:click="markLogoForRemoval"
                            data-test="remove-logo"
                        >{{ __('Remove logo') }}</flux:button>
                    @endif

                    <flux:error name="logo" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Theme colour') }}</flux:label>

                    <div class="flex flex-wrap gap-2" role="group" aria-label="{{ __('Preset colours') }}">
                        @foreach ($this->presets as $preset)
                            <button
                                type="button"
                                wire:click="$set('primaryColor', '{{ $preset['value'] }}')"
                                title="{{ $preset['label'] }}"
                                aria-label="{{ $preset['label'] }}"
                                @class([
                                    'size-9 rounded-full ring-offset-2 transition-transform hover:scale-110',
                                    'ring-2 ring-black' => strtolower($this->previewColor) === strtolower($preset['value']),
                                ])
                                style="background: {{ $preset['value'] }}"
                            ></button>
                        @endforeach
                    </div>

                    <div class="mt-3 flex items-center gap-3">
                        <input
                            type="color"
                            wire:model.live="primaryColor"
                            aria-label="{{ __('Custom colour') }}"
                            class="h-11 w-14 cursor-pointer rounded-xl border-0 bg-black/4 p-1"
                        />

                        <flux:input
                            wire:model.blur="primaryColor"
                            class="max-w-[10rem] font-mono"
                            placeholder="#1d4ed8"
                            aria-label="{{ __('Hex colour value') }}"
                            data-test="color-input"
                        />
                    </div>

                    <flux:error name="primaryColor" />
                </flux:field>

                <button
                    type="submit"
                    data-test="save-organization"
                    class="self-start rounded-full px-5 py-2.5 text-sm font-medium transition-opacity hover:opacity-85 disabled:opacity-50"
                    style="background:var(--brand);color:var(--brand-foreground)"
                    wire:loading.attr="disabled"
                    wire:target="save"
                >
                    <span wire:loading.remove wire:target="save">{{ __('Save changes') }}</span>
                    <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
                </button>
            </form>

            <div>
                <p class="text-xs font-medium tracking-[0.14em] text-black/35 uppercase">
                    {{ __('Live preview') }}
                </p>

                <div class="mt-4 rounded-2xl bg-black/4 p-2">
                    <x-organization-preview
                        :name="trim($name) !== '' ? $name : __('Your company')"
                        :color="$this->previewColor"
                        :logo-url="$this->previewLogoUrl"
                        class="!rounded-xl !border-black/8"
                    />
                </div>
            </div>
        </div>
    </x-pages::settings.layout>
</section>
