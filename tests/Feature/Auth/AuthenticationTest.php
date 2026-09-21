<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_is_reachable(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_a_user_can_log_in_and_lands_in_their_own_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_user_cannot_log_in_with_a_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_deactivated_user_cannot_log_in_and_is_told_why(): void
    {
        $user = User::factory()->inactive()->create(['password' => 'secret-password']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertSessionHasErrors([
            'email' => 'Your account has been deactivated. Please contact your administrator.',
        ]);

        $this->assertGuest();
    }

    public function test_a_user_of_a_deactivated_organization_cannot_log_in_and_is_told_why(): void
    {
        $organization = Organization::factory()->inactive()->create();
        $user = User::factory()->for($organization)->create(['password' => 'secret-password']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertSessionHasErrors([
            'email' => 'Your organization has been deactivated. Please contact Startsuite support.',
        ]);

        $this->assertGuest();
    }

    public function test_a_user_deactivated_mid_session_is_logged_out_on_the_next_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_user_whose_organization_is_deactivated_mid_session_is_logged_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->organization->forceFill(['is_active' => false])->save();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guests_cannot_reach_the_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_user_can_log_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect();

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // The throttle now rejects even the correct password.
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertTooManyRequests();

        $this->assertGuest();
    }
}
