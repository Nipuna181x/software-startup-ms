<?php

namespace Tests\Feature\Calling;

use App\Models\Calling\Business;
use App\Models\Calling\CallCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CallBusinessTypeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_any_member_can_view_business_types_within_a_category(): void
    {
        $user = User::factory()->create();
        $category = CallCategory::factory()->for($user->organization)->create(['name' => 'Small Businesses']);

        $this->actingAs($user)
            ->get(route('calling.types.index', $category))
            ->assertOk()
            ->assertSee('Small Businesses');
    }

    public function test_a_super_admin_can_add_a_business_type(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $category = CallCategory::factory()->for($admin->organization)->create();

        Livewire::actingAs($admin)
            ->test('pages::calling.types.index', ['category' => $category])
            ->call('addType')
            ->set('name', 'Phone Shops')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('call_business_types', [
            'call_category_id' => $category->id,
            'name' => 'Phone Shops',
        ]);
    }

    public function test_a_regular_user_cannot_add_a_business_type(): void
    {
        $user = User::factory()->create();
        $category = CallCategory::factory()->for($user->organization)->create();

        Livewire::actingAs($user)
            ->test('pages::calling.types.index', ['category' => $category])
            ->call('addType')
            ->assertForbidden();
    }

    public function test_a_business_type_with_businesses_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $category = CallCategory::factory()->for($admin->organization)->create();
        $type = $category->businessTypes()->create([
            'organization_id' => $admin->organization_id,
            'name' => 'Phone Shops',
        ]);
        Business::factory()->for($type, 'type')->for($admin->organization)->create();

        Livewire::actingAs($admin)
            ->test('pages::calling.types.index', ['category' => $category])
            ->call('confirmDelete', $type->id);

        $this->assertDatabaseHas('call_business_types', ['id' => $type->id]);
    }

    public function test_a_super_admin_can_delete_an_empty_business_type(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $category = CallCategory::factory()->for($admin->organization)->create();
        $type = $category->businessTypes()->create([
            'organization_id' => $admin->organization_id,
            'name' => 'Phone Shops',
        ]);

        Livewire::actingAs($admin)
            ->test('pages::calling.types.index', ['category' => $category])
            ->call('confirmDelete', $type->id)
            ->call('deleteType');

        $this->assertDatabaseMissing('call_business_types', ['id' => $type->id]);
    }
}
