<div class="flex items-start gap-6 max-md:flex-col">
    <nav class="w-full shrink-0 pb-2 md:w-[175px]" aria-label="{{ __('Settings') }}">
        <ul class="flex gap-1.5 md:flex-col">
            @php($tabs = [['route' => 'profile.edit', 'label' => __('Profile'), 'show' => true], ['route' => 'organization.edit', 'label' => __('Organization'), 'show' => auth()->user()?->isSuperAdmin()]])

            @foreach ($tabs as $tab)
                @if ($tab['show'])
                    @php($isCurrent = request()->routeIs($tab['route']))
                    <li>
                        <a
                            href="{{ route($tab['route']) }}"
                            wire:navigate
                            @class([
                                'block rounded-xl px-3.5 py-2.5 text-sm transition-colors',
                                'font-medium' => $isCurrent,
                                'text-black/60 hover:bg-black/4 hover:text-black' => ! $isCurrent,
                            ])
                            @if ($isCurrent)
                                style="background:var(--brand-subtle);color:var(--brand-active)"
                            @endif
                        >{{ $tab['label'] }}</a>
                    </li>
                @endif
            @endforeach
        </ul>
    </nav>

    <div class="settings-card min-w-0 flex-1 self-stretch">
        <h2 class="text-[17px] font-medium tracking-tight">{{ $heading ?? '' }}</h2>
        <p class="mt-1.5 text-sm text-black/55">{{ $subheading ?? '' }}</p>

        <div class="mt-7 w-full {{ $wide ?? false ? '' : 'max-w-lg' }}">
            {{ $slot }}
        </div>
    </div>
</div>
