<?php

namespace Tests\Feature\Settings;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class OrganizationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_super_admin_can_open_organization_settings(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('organization.edit'))
            ->assertOk()
            ->assertSee('Organization')
            ->assertDontSee('Theme colour')
            ->assertDontSee('data-test="color-input"', escape: false);
    }

    public function test_a_regular_user_gets_a_403_from_organization_settings(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('organization.edit'))
            ->assertForbidden();
    }

    public function test_a_super_admin_can_change_the_name_without_changing_the_legacy_colour_value(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $originalColor = $admin->organization->primary_color;

        Livewire::actingAs($admin)
            ->test('pages::settings.organization')
            ->set('name', 'Renamed Company')
            ->call('save')
            ->assertHasNoErrors();

        $organization = $admin->organization->refresh();

        $this->assertSame('Renamed Company', $organization->name);
        $this->assertSame($originalColor, $organization->primary_color);
    }

    public function test_a_super_admin_can_replace_and_remove_the_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test('pages::settings.organization')
            ->set('logo', UploadedFile::fake()->image('logo.png'))
            ->call('save')
            ->assertHasNoErrors();

        $organization = $admin->organization->refresh();
        $this->assertNotNull($organization->logo_path);
        Storage::disk('public')->assertExists($organization->logo_path);

        $storedPath = $organization->logo_path;

        Livewire::actingAs($admin)
            ->test('pages::settings.organization')
            ->call('markLogoForRemoval')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($admin->organization->refresh()->logo_path);
        Storage::disk('public')->assertMissing($storedPath);
    }

    public function test_it_rejects_an_svg_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test('pages::settings.organization')
            ->set('logo', UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'))
            ->call('save')
            ->assertHasErrors('logo');

        $this->assertNull($admin->organization->refresh()->logo_path);
    }

    public function test_the_legacy_theme_colour_does_not_reach_the_dashboard(): void
    {
        $organization = Organization::factory()->create(['primary_color' => '#0f766e']);
        $admin = User::factory()->superAdmin()->for($organization)->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('#0f766e');
    }

    public function test_the_sidebar_shows_the_company_name_and_the_startsuite_wordmark(): void
    {
        $organization = Organization::factory()->create(['name' => 'Northwind Logistics']);
        $admin = User::factory()->superAdmin()->for($organization)->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Northwind Logistics')
            ->assertSee('Startsuite');
    }
}
