<?php

namespace Tests\Feature\Calling;

use App\Models\Calling\CallCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Proves that one organization's Calling data is never visible or editable
 * from another organization, including by guessing ids or route parameters.
 */
class CallingDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organizationA;

    private Organization $organizationB;

    private User $adminA;

    private CallCategory $categoryB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizationA = Organization::factory()->create(['name' => 'Alpha Company']);
        $this->organizationB = Organization::factory()->create(['name' => 'Beta Company']);

        $this->adminA = User::factory()->superAdmin()->for($this->organizationA)->create();
        $this->categoryB = CallCategory::factory()->for($this->organizationB)->create(['name' => 'Beta Category']);
    }

    public function test_queries_only_return_categories_of_the_viewers_organization(): void
    {
        CallCategory::factory()->for($this->organizationA)->create();

        $this->actingAs($this->adminA);

        $this->assertSame(1, CallCategory::query()->count());
        $this->assertNull(CallCategory::query()->find($this->categoryB->id));
    }

    public function test_visiting_another_organizations_category_by_slug_returns_404(): void
    {
        $this->actingAs($this->adminA)
            ->get(route('calling.types.index', $this->categoryB))
            ->assertNotFound();
    }

    public function test_the_categories_list_never_shows_another_organizations_categories(): void
    {
        Livewire::actingAs($this->adminA)
            ->test('pages::calling.categories.index')
            ->assertDontSee('Beta Category');
    }

    public function test_editing_another_organizations_category_by_id_fails(): void
    {
        try {
            Livewire::actingAs($this->adminA)
                ->test('pages::calling.categories.index')
                ->call('editCategory', $this->categoryB->id);

            $this->fail('Editing a category from another organization should not succeed.');
        } catch (ModelNotFoundException $exception) {
            $this->assertStringContainsString(CallCategory::class, $exception->getMessage());
        }
    }

    public function test_deleting_another_organizations_category_by_id_fails(): void
    {
        try {
            Livewire::actingAs($this->adminA)
                ->test('pages::calling.categories.index')
                ->call('confirmDelete', $this->categoryB->id);

            $this->fail('Deleting a category from another organization should not succeed.');
        } catch (ModelNotFoundException $exception) {
            $this->assertStringContainsString(CallCategory::class, $exception->getMessage());
        }

        $this->assertDatabaseHas('call_categories', ['id' => $this->categoryB->id]);
    }

    public function test_a_new_category_is_stamped_with_the_creators_organization(): void
    {
        $this->actingAs($this->adminA);

        $category = CallCategory::create(['name' => 'Stamped Category']);

        $this->assertSame($this->organizationA->id, $category->organization_id);
    }

    public function test_the_policy_denies_cross_organization_access_even_without_the_global_scope(): void
    {
        // Simulates a future module querying without the scope by mistake:
        // the policy is a second, independent line of defence.
        $categoryB = CallCategory::withoutGlobalScopes()->find($this->categoryB->id);

        $this->assertFalse($this->adminA->can('view', $categoryB));
        $this->assertFalse($this->adminA->can('update', $categoryB));
        $this->assertFalse($this->adminA->can('delete', $categoryB));
    }
}
