<?php

namespace Tests\Feature\Calling;

use App\Models\Calling\Business;
use App\Models\Calling\CallBusinessType;
use App\Models\Calling\CallCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Proves that one organization's businesses are never visible or editable
 * from another organization, including by guessing ids or route parameters.
 */
class BusinessDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organizationA;

    private Organization $organizationB;

    private User $adminA;

    private CallBusinessType $typeA;

    private Business $businessB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizationA = Organization::factory()->create();
        $this->organizationB = Organization::factory()->create();

        $this->adminA = User::factory()->superAdmin()->for($this->organizationA)->create();

        $categoryA = CallCategory::factory()->for($this->organizationA)->create();
        $this->typeA = $categoryA->businessTypes()->create([
            'organization_id' => $this->organizationA->id,
            'name' => 'Phone Shops',
        ]);

        $categoryB = CallCategory::factory()->for($this->organizationB)->create();
        $typeB = $categoryB->businessTypes()->create([
            'organization_id' => $this->organizationB->id,
            'name' => 'Beta Type',
        ]);
        $this->businessB = Business::factory()->for($typeB, 'type')->for($this->organizationB)->create(['name' => 'Beta Business']);
    }

    public function test_queries_only_return_businesses_of_the_viewers_organization(): void
    {
        Business::factory()->for($this->typeA, 'type')->for($this->organizationA)->create();

        $this->actingAs($this->adminA);

        $this->assertSame(1, Business::query()->count());
        $this->assertNull(Business::query()->find($this->businessB->id));
    }

    public function test_the_businesses_list_never_shows_another_organizations_businesses(): void
    {
        Livewire::actingAs($this->adminA)
            ->test('pages::calling.businesses.index', ['category' => $this->typeA->category, 'type' => $this->typeA])
            ->assertDontSee('Beta Business');
    }

    public function test_visiting_another_organizations_business_by_id_returns_404(): void
    {
        // Resolve org B's category/type before switching to org A, since the
        // global scope would otherwise hide them from the acting user too.
        $typeB = CallBusinessType::withoutGlobalScopes()->find($this->businessB->call_business_type_id);
        $categoryB = CallCategory::withoutGlobalScopes()->find($typeB->call_category_id);

        $this->actingAs($this->adminA)
            ->get(route('calling.businesses.show', [$categoryB, $typeB, $this->businessB]))
            ->assertNotFound();
    }

    public function test_editing_another_organizations_business_by_id_fails(): void
    {
        try {
            Livewire::actingAs($this->adminA)
                ->test('pages::calling.businesses.index', ['category' => $this->typeA->category, 'type' => $this->typeA])
                ->call('editBusiness', $this->businessB->id);

            $this->fail('Editing a business from another organization should not succeed.');
        } catch (ModelNotFoundException $exception) {
            $this->assertStringContainsString(Business::class, $exception->getMessage());
        }
    }

    public function test_deleting_another_organizations_business_by_id_fails(): void
    {
        try {
            Livewire::actingAs($this->adminA)
                ->test('pages::calling.businesses.index', ['category' => $this->typeA->category, 'type' => $this->typeA])
                ->call('confirmDelete', $this->businessB->id);

            $this->fail('Deleting a business from another organization should not succeed.');
        } catch (ModelNotFoundException $exception) {
            $this->assertStringContainsString(Business::class, $exception->getMessage());
        }

        $this->assertDatabaseHas('businesses', ['id' => $this->businessB->id]);
    }

    public function test_the_policy_denies_cross_organization_access_even_without_the_global_scope(): void
    {
        $businessB = Business::withoutGlobalScopes()->find($this->businessB->id);

        $this->assertFalse($this->adminA->can('view', $businessB));
        $this->assertFalse($this->adminA->can('update', $businessB));
        $this->assertFalse($this->adminA->can('delete', $businessB));
    }
}
