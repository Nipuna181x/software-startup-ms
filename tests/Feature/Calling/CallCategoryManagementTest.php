<?php

namespace Tests\Feature\Calling;

use App\Models\Calling\CallCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CallCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_member_can_view_the_categories_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('calling.categories.index'))
            ->assertOk()
            ->assertSee('Calling');
    }

    public function test_a_super_admin_can_add_a_category(): void
    {
        $admin = User::factory()->superAdmin()->create();

        Livewire::actingAs($admin)
            ->test('pages::calling.categories.index')
            ->call('addCategory')
            ->set('name', 'Marketing Agencies')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('call_categories', [
            'organization_id' => $admin->organization_id,
            'name' => 'Marketing Agencies',
        ]);
    }

    public function test_a_regular_user_cannot_add_a_category(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::calling.categories.index')
            ->call('addCategory')
            ->assertForbidden();

        $this->assertDatabaseMissing('call_categories', ['organization_id' => $user->organization_id]);
    }

    public function test_a_regular_user_cannot_save_a_category_by_calling_the_action_directly(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('pages::calling.categories.index')
            ->set('name', 'Smuggled Category')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseMissing('call_categories', ['name' => 'Smuggled Category']);
    }

    public function test_a_super_admin_can_edit_a_category(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $category = CallCategory::factory()->for($admin->organization)->create(['name' => 'Old Name']);

        Livewire::actingAs($admin)
            ->test('pages::calling.categories.index')
            ->call('editCategory', $category->id)
            ->set('name', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('New Name', $category->refresh()->name);
    }

    public function test_a_regular_user_cannot_edit_a_category(): void
    {
        $user = User::factory()->create();
        $category = CallCategory::factory()->for($user->organization)->create();

        Livewire::actingAs($user)
            ->test('pages::calling.categories.index')
            ->call('editCategory', $category->id)
            ->assertForbidden();
    }

    public function test_a_super_admin_can_delete_an_empty_category(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $category = CallCategory::factory()->for($admin->organization)->create();

        Livewire::actingAs($admin)
            ->test('pages::calling.categories.index')
            ->call('confirmDelete', $category->id)
            ->call('deleteCategory');

        $this->assertDatabaseMissing('call_categories', ['id' => $category->id]);
    }

    public function test_a_category_with_business_types_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $category = CallCategory::factory()->for($admin->organization)->create();
        $category->businessTypes()->create([
            'organization_id' => $admin->organization_id,
            'name' => 'Phone Shops',
        ]);

        Livewire::actingAs($admin)
            ->test('pages::calling.categories.index')
            ->call('confirmDelete', $category->id);

        $this->assertDatabaseHas('call_categories', ['id' => $category->id]);
    }

    public function test_a_regular_user_cannot_delete_a_category(): void
    {
        $user = User::factory()->create();
        $category = CallCategory::factory()->for($user->organization)->create();

        Livewire::actingAs($user)
            ->test('pages::calling.categories.index')
            ->call('confirmDelete', $category->id)
            ->assertForbidden();

        $this->assertDatabaseHas('call_categories', ['id' => $category->id]);
    }
}
