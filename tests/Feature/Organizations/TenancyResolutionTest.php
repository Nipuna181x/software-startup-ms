<?php

namespace Tests\Feature\Organizations;

use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Guards the re-entrancy of tenant resolution.
 *
 * Resolving the authenticated user issues a User query, which re-enters the
 * global scope. Without a guard that recursed until PHP's execution timeout,
 * so every authenticated page returned a 500 after 30 seconds.
 */
class TenancyResolutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_real_login_session_can_load_authenticated_pages(): void
    {
        $user = User::factory()->superAdmin()->create(['password' => 'secret-password']);

        // Log in over HTTP so the session guard resolves the user from the
        // database, rather than being handed a pre-built user by actingAs().
        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('users.index'))->assertOk();
        $this->get(route('profile.edit'))->assertOk();
        $this->get(route('organization.edit'))->assertOk();
    }

    public function test_resolving_the_organization_does_not_recurse(): void
    {
        $user = User::factory()->create();

        // Authenticate without handing the guard a resolved user, so calling
        // Auth::user() genuinely issues the User query that re-enters the
        // global scope. This is what a real browser session does.
        Auth::guard('web')->loginUsingId($user->id);
        Auth::guard('web')->forgetUser();

        $this->assertSame($user->organization_id, Tenancy::currentOrganizationId());
    }

    public function test_the_re_entrancy_guard_is_released_after_resolution(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        Auth::guard('web')->forgetUser();

        // Called twice: a guard left stuck would make the second call return null.
        Tenancy::currentOrganizationId();

        $this->assertSame($user->organization_id, Tenancy::currentOrganizationId());
    }
}
