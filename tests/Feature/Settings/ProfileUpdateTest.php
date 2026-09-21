<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_profile_page_is_reachable(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_settings_redirects_to_the_profile_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings')
            ->assertRedirect('/settings/profile');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::settings.profile')
            ->set('name', 'Renamed Person')
            ->set('email', 'renamed@example.test')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Renamed Person', $user->name);
        $this->assertSame('renamed@example.test', $user->email);
    }

    public function test_the_email_must_stay_unique_across_the_platform(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['email' => 'taken@example.test']);

        Livewire::actingAs($user)
            ->test('pages::settings.profile')
            ->set('email', 'taken@example.test')
            ->call('updateProfileInformation')
            ->assertHasErrors('email');
    }

    public function test_a_user_can_change_their_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        Livewire::actingAs($user)
            ->test('pages::settings.profile')
            ->set('current_password', 'old-password')
            ->set('password', 'a-new-password')
            ->set('password_confirmation', 'a-new-password')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('a-new-password', $user->refresh()->password));
    }

    public function test_the_current_password_must_be_correct_to_change_it(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        Livewire::actingAs($user)
            ->test('pages::settings.profile')
            ->set('current_password', 'wrong-password')
            ->set('password', 'a-new-password')
            ->set('password_confirmation', 'a-new-password')
            ->call('updatePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->refresh()->password));
    }

    public function test_a_regular_user_sees_no_organization_tab(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee(route('organization.edit'));
    }
}
