<?php

namespace Tests\Feature\Organizations;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class RegisterOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_registration_page_is_reachable(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Register your company')
            ->assertDontSee('Theme colour')
            ->assertDontSee('data-test="color-input"', escape: false);
    }

    public function test_it_creates_the_organization_and_its_first_super_admin(): void
    {
        Livewire::test('pages::auth.register')
            ->set('organizationName', 'Northwind Logistics')
            ->set('name', 'Amara Osei')
            ->set('email', 'amara@northwind.test')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $organization = Organization::firstWhere('name', 'Northwind Logistics');

        $this->assertNotNull($organization);
        $this->assertSame('northwind-logistics', $organization->slug);
        $this->assertSame(config('startsuite.brand_color'), $organization->primary_color);
        $this->assertTrue($organization->is_active);

        $admin = User::withoutGlobalScopes()->firstWhere('email', 'amara@northwind.test');

        $this->assertNotNull($admin);
        $this->assertSame($organization->id, $admin->organization_id);
        $this->assertSame(Role::SuperAdmin, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertNotNull($admin->email_verified_at);

        $this->assertAuthenticatedAs($admin);
    }

    public function test_it_stores_an_uploaded_logo(): void
    {
        Storage::fake('public');

        Livewire::test('pages::auth.register')
            ->set('organizationName', 'Halden and Rowe')
            ->set('logo', UploadedFile::fake()->image('logo.png', 320, 320))
            ->set('name', 'Tom Beckett')
            ->set('email', 'tom@halden.test')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertHasNoErrors();

        $organization = Organization::firstWhere('name', 'Halden and Rowe');

        $this->assertNotNull($organization->logo_path);
        Storage::disk('public')->assertExists($organization->logo_path);
    }

    public function test_it_rejects_an_svg_logo(): void
    {
        Storage::fake('public');

        Livewire::test('pages::auth.register')
            ->set('organizationName', 'Meridian Labs')
            ->set('logo', UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'))
            ->set('name', 'Priya Raman')
            ->set('email', 'priya@meridian.test')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertHasErrors('logo');

        $this->assertDatabaseMissing('organizations', ['name' => 'Meridian Labs']);
    }

    public function test_it_rejects_a_logo_over_two_megabytes(): void
    {
        Storage::fake('public');

        Livewire::test('pages::auth.register')
            ->set('logo', UploadedFile::fake()->image('logo.png')->size(2049))
            ->assertHasErrors('logo');
    }

    public function test_it_requires_a_unique_email_across_the_whole_platform(): void
    {
        $existing = User::factory()->create(['email' => 'taken@example.test']);

        Livewire::test('pages::auth.register')
            ->set('organizationName', 'Second Company')
            ->set('name', 'Someone Else')
            ->set('email', 'taken@example.test')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertHasErrors('email');

        $this->assertDatabaseMissing('organizations', ['name' => 'Second Company']);
        $this->assertSame(1, User::withoutGlobalScopes()->where('email', 'taken@example.test')->count());
    }

    public function test_it_requires_the_password_confirmation_to_match(): void
    {
        Livewire::test('pages::auth.register')
            ->set('organizationName', 'Mismatch Ltd')
            ->set('name', 'Someone')
            ->set('email', 'someone@mismatch.test')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'different-password')
            ->call('register')
            ->assertHasErrors('password');

        $this->assertDatabaseMissing('organizations', ['name' => 'Mismatch Ltd']);
    }

    public function test_it_gives_each_organization_a_unique_slug(): void
    {
        Organization::factory()->create(['name' => 'Duplicate Co', 'slug' => 'duplicate-co']);

        Livewire::test('pages::auth.register')
            ->set('organizationName', 'Duplicate Co')
            ->set('name', 'Second Owner')
            ->set('email', 'second@duplicate.test')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('register')
            ->assertHasNoErrors();

        $this->assertSame(2, Organization::where('name', 'Duplicate Co')->count());
        $this->assertDatabaseHas('organizations', ['slug' => 'duplicate-co-2']);
    }

    public function test_the_public_self_registration_route_does_not_exist(): void
    {
        $this->assertFalse(
            collect(app('router')->getRoutes())->contains(
                fn ($route) => $route->uri() === 'register' && in_array('POST', $route->methods(), true),
            ),
            'Fortify self-registration POST route should be disabled.',
        );
    }
}
