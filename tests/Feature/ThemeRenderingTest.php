<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThemeRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_resolves_the_organization_only_once_per_request(): void
    {
        $organization = Organization::factory()->create(['name' => 'Northwind Logistics']);
        $user = User::factory()->superAdmin()->for($organization)->create();

        $this->actingAs($user);

        DB::enableQueryLog();

        $this->get(route('dashboard'))->assertOk();

        $organizationQueries = collect(DB::getQueryLog())
            ->filter(fn (array $entry): bool => str_contains($entry['query'], 'from `organizations`'))
            ->count();

        DB::disableQueryLog();

        // The view composer runs for every nested view, so an unmemoised
        // lookup here previously caused a runaway number of queries.
        $this->assertLessThanOrEqual(
            2,
            $organizationQueries,
            'The organization should be resolved once per request, not per view.',
        );
    }

    public function test_the_workspace_ignores_legacy_organization_colours(): void
    {
        $organization = Organization::factory()->create(['primary_color' => '#ff00ff']);
        $user = User::factory()->superAdmin()->for($organization)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('#ff00ff')
            ->assertDontSee('style="--brand', escape: false)
            ->assertDontSee('Good work starts here.');
    }

    public function test_the_sidebar_shows_initials_when_there_is_no_logo(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Northwind Logistics',
            'logo_path' => null,
        ]);
        $user = User::factory()->superAdmin()->for($organization)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('NL')
            ->assertDontSee('<img src="/storage/organization-logos', escape: false);
    }

    public function test_the_sidebar_shows_the_logo_when_one_is_uploaded(): void
    {
        $organization = Organization::factory()->create([
            'name' => 'Northwind Logistics',
            'logo_path' => 'organization-logos/example.png',
        ]);
        $user = User::factory()->superAdmin()->for($organization)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('/storage/organization-logos/example.png', escape: false);
    }

    public function test_a_regular_user_does_not_see_the_users_link_in_the_sidebar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('users.index'));
    }

    public function test_a_super_admin_sees_the_users_link_in_the_sidebar(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('users.index'));
    }

    public function test_the_public_pages_render_for_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Startsuite')
            ->assertSee('Your company gets');

        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
    }
}
