<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class OrganizationDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_see_all_organizations_and_counts_even_with_an_organization_session(): void
    {
        $this->freezeTime();
        $alpha = Organization::factory()->create(['name' => 'Alpha Studio']);
        $beta = Organization::factory()->inactive()->create(['name' => 'Beta Studio']);
        $user = User::factory()->for($alpha)->create();
        User::factory()->count(2)->for($beta)->create();
        $this->actingAs(PlatformAdmin::factory()->create(), 'admin')->actingAs($user, 'web');

        $this->get(route('admin.dashboard'))
            ->assertSee('Alpha Studio')->assertSee('Beta Studio')
            ->assertViewHas('stats', ['Organizations' => 2, 'Active workspaces' => 1, 'Registered users' => 3, 'New this month' => 2])
            ->assertViewHas('organizations', fn ($organizations): bool => $organizations->firstWhere('id', $beta->id)->users_count === 2);

        $this->assertSame(1, User::query()->count());
    }

    public function test_search_and_status_filters_are_applied_together(): void
    {
        Organization::factory()->create(['name' => 'Alpha Active']);
        Organization::factory()->inactive()->create(['name' => 'Alpha Inactive']);
        Organization::factory()->create(['name' => 'Beta Active']);
        $this->signInAdmin();

        $this->get(route('admin.dashboard', ['search' => 'Alpha', 'status' => 'active']))
            ->assertSee('Alpha Active')->assertDontSee('Alpha Inactive')->assertDontSee('Beta Active');
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $this->signInAdmin();

        $this->get(route('admin.dashboard', ['status' => 'anything']))->assertSessionHasErrors('status');
    }

    public function test_search_without_matches_has_a_clear_empty_state(): void
    {
        Organization::factory()->create();
        $this->signInAdmin();

        $this->get(route('admin.dashboard', ['search' => 'nothing-matches-this']))
            ->assertSee('No organizations found');
    }

    public function test_organization_directory_is_paginated(): void
    {
        Organization::factory()->count(13)->create();
        $this->signInAdmin();

        $this->get(route('admin.dashboard'))->assertViewHas('organizations', fn ($organizations): bool => $organizations->count() === 12 && $organizations->total() === 13);
        $this->get(route('admin.dashboard', ['page' => 2]))->assertViewHas('organizations', fn ($organizations): bool => $organizations->count() === 1);
    }

    public function test_details_only_show_members_of_the_selected_organization(): void
    {
        $alpha = Organization::factory()->create();
        $beta = Organization::factory()->create();
        User::factory()->for($alpha)->create(['name' => 'Alpha Person']);
        User::factory()->for($beta)->create(['name' => 'Beta Person']);
        $this->signInAdmin();

        $this->get(route('admin.organizations.show', $beta))
            ->assertSee('Beta Person')->assertDontSee('Alpha Person');
    }

    public function test_organization_users_cannot_open_platform_organization_details(): void
    {
        $organization = Organization::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.organizations.show', $organization))->assertRedirectToRoute('admin.login');
    }

    public function test_unknown_organization_returns_not_found_to_admins(): void
    {
        $this->signInAdmin();

        $this->get(route('admin.organizations.show', 'does-not-exist'))->assertNotFound();
    }

    public function test_organization_and_member_names_are_escaped(): void
    {
        $organization = Organization::factory()->create(['name' => '<script>alert(1)</script>']);
        User::factory()->for($organization)->create(['name' => '<script>alert(2)</script>']);
        $this->signInAdmin();

        $this->get(route('admin.dashboard'))->assertSee($organization->name)->assertDontSee($organization->name, false);
        $this->get(route('admin.organizations.show', $organization))
            ->assertSee('<script>alert(2)</script>')->assertDontSee('<script>alert(2)</script>', false);
    }

    private function signInAdmin(): void
    {
        $this->actingAs(PlatformAdmin::factory()->create(), 'admin');
        Auth::shouldUse('web');
    }
}
