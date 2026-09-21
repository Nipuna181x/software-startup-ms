<?php

namespace Tests\Feature\Admin;

use App\Models\PlatformAdmin;
use App\Models\User;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlatformAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_platform_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirectToRoute('admin.login');
        $this->get(route('admin.login'))->assertOk();
    }

    public function test_organization_administrators_cannot_access_platform_administration(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirectToRoute('admin.login');
        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_regular_users_cannot_access_platform_administration(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))->assertRedirectToRoute('admin.login');
    }

    public function test_valid_admin_credentials_start_a_separate_session(): void
    {
        $admin = PlatformAdmin::factory()->create();

        $this->post(route('admin.login.store'), ['email' => strtoupper($admin->email), 'password' => 'password'])
            ->assertRedirectToRoute('admin.dashboard');

        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');
        $this->get(route('admin.login'))->assertRedirectToRoute('admin.dashboard');
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $admin = PlatformAdmin::factory()->create();

        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'incorrect'])
            ->assertSessionHasErrors(['email' => __('auth.failed')]);

        $this->assertGuest('admin');
    }

    public function test_login_requires_an_email_and_password(): void
    {
        $this->post(route('admin.login.store'), [])
            ->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest('admin');
    }

    public function test_inactive_admins_cannot_sign_in(): void
    {
        $admin = PlatformAdmin::factory()->inactive()->create();

        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
    }

    public function test_deactivated_admins_lose_access_on_the_next_request(): void
    {
        $admin = PlatformAdmin::factory()->create();
        $this->actingAs($admin, 'admin');
        Auth::shouldUse('web');
        $admin->update(['is_active' => false]);

        $this->get(route('admin.dashboard'))->assertRedirectToRoute('admin.login');

        $this->assertGuest('admin');
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $this->freezeTime();
        $admin = PlatformAdmin::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'incorrect']);
        }

        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Too many login attempts. Try again in 60 seconds.']);

        $this->assertGuest('admin');
    }

    public function test_admin_logout_preserves_the_organization_session(): void
    {
        $user = User::factory()->create();
        $admin = PlatformAdmin::factory()->create();
        $this->actingAs($admin, 'admin')->actingAs($user, 'web');

        $this->post(route('admin.logout'))->assertRedirectToRoute('admin.login');

        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_seeder_creates_a_hashed_account_without_overwriting_existing_credentials(): void
    {
        config(['startsuite.platform_admin.email' => 'admin@startsuite.test', 'startsuite.platform_admin.password' => 'password']);

        $this->seed(PlatformAdminSeeder::class);

        $admin = PlatformAdmin::query()->sole();
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->post(route('admin.login.store'), ['email' => 'admin@startsuite.test', 'password' => 'password'])
            ->assertRedirectToRoute('admin.dashboard');

        config(['startsuite.platform_admin.password' => 'different-password']);
        $this->seed(PlatformAdminSeeder::class);
        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
        $this->assertDatabaseCount('platform_admins', 1);
    }

    public function test_seeder_does_not_create_an_account_without_a_configured_password(): void
    {
        config(['startsuite.platform_admin.password' => null]);

        $this->seed(PlatformAdminSeeder::class);

        $this->assertDatabaseEmpty('platform_admins');
    }
}
