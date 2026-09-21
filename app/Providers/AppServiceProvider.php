<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Navigation;
use App\Support\OrganizationContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(OrganizationContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureGates();
        $this->configureNavigation();
        $this->configureViewSharing();
    }

    /**
     * Share the viewer's organization with every view.
     *
     * A composer rather than middleware, so the variables also exist when a
     * Livewire component is rendered outside the HTTP middleware stack.
     */
    protected function configureViewSharing(): void
    {
        View::composer('*', function ($view): void {
            $context = app(OrganizationContext::class);

            $view->with('organization', $context->organization());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Register application gates.
     */
    protected function configureGates(): void
    {
        Gate::define('super-admin', fn (User $user): bool => $user->isSuperAdmin());
    }

    /**
     * Register the default dashboard sidebar items.
     *
     * Modules added later append their own entries the same way.
     */
    protected function configureNavigation(): void
    {
        Navigation::register(
            label: 'Dashboard',
            route: 'dashboard',
            icon: 'home',
            group: 'Platform',
            pattern: 'dashboard',
            order: 10,
        );

        Navigation::register(
            label: 'Calling',
            route: 'calling.categories.index',
            icon: 'phone',
            group: 'Platform',
            pattern: 'calling.*',
            order: 15,
        );

        Navigation::register(
            label: 'Users',
            route: 'users.index',
            icon: 'users',
            group: 'Platform',
            pattern: 'users.*',
            order: 20,
            visible: fn (User $user): bool => $user->isSuperAdmin(),
        );

        Navigation::register(
            label: 'Settings',
            route: 'profile.edit',
            icon: 'cog-6-tooth',
            group: 'Platform',
            pattern: 'settings.*|profile.edit|organization.edit',
            order: 30,
        );
    }
}
