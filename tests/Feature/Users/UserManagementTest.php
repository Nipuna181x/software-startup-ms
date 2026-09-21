<?php

namespace Tests\Feature\Users;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_super_admin_can_open_the_users_page(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Users');
    }

    public function test_a_regular_user_gets_a_403_from_the_users_url(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_a_super_admin_can_add_a_user_who_is_verified_immediately(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('addUser')
            ->set('name', 'Tom Beckett')
            ->set('email', 'tom@example.test')
            ->set('role', Role::User->value)
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('save')
            ->assertHasNoErrors();

        $created = User::withoutGlobalScopes()->firstWhere('email', 'tom@example.test');

        $this->assertNotNull($created);
        $this->assertSame($admin->organization_id, $created->organization_id);
        $this->assertSame(Role::User, $created->role);
        $this->assertTrue($created->is_active);
        $this->assertNotNull($created->email_verified_at);
    }

    public function test_a_super_admin_can_edit_a_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $member = User::factory()->for($admin->organization)->create();

        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('editUser', $member->id)
            ->set('name', 'Renamed Person')
            ->set('role', Role::SuperAdmin->value)
            ->call('save')
            ->assertHasNoErrors();

        $member->refresh();

        $this->assertSame('Renamed Person', $member->name);
        $this->assertSame(Role::SuperAdmin, $member->role);
    }

    public function test_a_super_admin_can_reset_another_users_password(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $member = User::factory()->for($admin->organization)->create();

        $originalPassword = $member->password;

        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('startPasswordReset', $member->id)
            ->set('password', 'a-brand-new-password')
            ->set('password_confirmation', 'a-brand-new-password')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertNotSame($originalPassword, $member->refresh()->password);
    }

    public function test_the_generate_password_button_fills_both_password_fields(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $component = Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('generatePassword');

        $password = $component->get('password');

        $this->assertNotSame('', $password);
        $this->assertSame($password, $component->get('password_confirmation'));
    }

    public function test_a_super_admin_can_deactivate_and_reactivate_another_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $member = User::factory()->for($admin->organization)->create();

        $component = Livewire::actingAs($admin)->test('pages::users.index');

        $component->call('toggleActive', $member->id);
        $this->assertFalse($member->refresh()->is_active);

        $component->call('toggleActive', $member->id);
        $this->assertTrue($member->refresh()->is_active);
    }

    public function test_a_super_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $member = User::factory()->for($admin->organization)->create();

        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('confirmDelete', $member->id)
            ->call('deleteUser');

        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }

    public function test_a_super_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->superAdmin()->for($admin->organization)->create();

        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('confirmDelete', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_a_super_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->superAdmin()->for($admin->organization)->create();

        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('toggleActive', $admin->id);

        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_the_last_super_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $other = User::factory()->superAdmin()->for($admin->organization)->create();

        // Removing the second Super Admin leaves exactly one.
        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('confirmDelete', $other->id)
            ->call('deleteUser');

        $this->assertDatabaseMissing('users', ['id' => $other->id]);

        // The remaining Super Admin cannot be removed by anyone.
        $member = User::factory()->for($admin->organization)->create(['role' => Role::SuperAdmin]);
        $member->forceFill(['role' => Role::User])->save();

        $this->assertFalse($member->fresh()->can('delete', $admin->fresh()));
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_the_last_super_admin_cannot_be_deactivated(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $member = User::factory()->for($admin->organization)->create();

        Livewire::actingAs($member)
            ->test('pages::users.index')
            ->assertForbidden();

        $this->assertFalse($admin->can('deactivate', $admin));
        $this->assertTrue($admin->refresh()->is_active);
    }

    public function test_the_last_super_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('editUser', $admin->id)
            ->set('role', Role::User->value)
            ->call('save')
            ->assertHasErrors('role');

        $this->assertSame(Role::SuperAdmin, $admin->refresh()->role);
    }

    public function test_a_super_admin_can_be_demoted_when_another_one_remains(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $other = User::factory()->superAdmin()->for($admin->organization)->create();

        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('editUser', $other->id)
            ->set('role', Role::User->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(Role::User, $other->refresh()->role);
    }

    public function test_the_list_can_be_searched(): void
    {
        $admin = User::factory()->superAdmin()->create(['name' => 'Amara Osei']);
        User::factory()->for($admin->organization)->create(['name' => 'Tom Beckett']);

        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->set('search', 'Beckett')
            ->assertSee('Tom Beckett')
            ->assertDontSee('Amara Osei');
    }

    public function test_emails_must_be_unique_across_the_platform(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $elsewhere = User::factory()->create(['email' => 'taken@example.test']);

        Livewire::actingAs($admin)
            ->test('pages::users.index')
            ->call('addUser')
            ->set('name', 'Someone New')
            ->set('email', 'taken@example.test')
            ->set('role', Role::User->value)
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('save')
            ->assertHasErrors('email');
    }
}
