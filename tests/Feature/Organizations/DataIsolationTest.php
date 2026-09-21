<?php

namespace Tests\Feature\Organizations;

use App\Models\Organization;
use App\Models\User;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Proves that one organization can never read or change another's data,
 * including by guessing ids or calling component actions directly.
 */
class DataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organizationA;

    private Organization $organizationB;

    private User $adminA;

    private User $adminB;

    private User $memberB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizationA = Organization::factory()->create(['name' => 'Alpha Company']);
        $this->organizationB = Organization::factory()->create(['name' => 'Beta Company']);

        $this->adminA = User::factory()->superAdmin()->for($this->organizationA)->create();
        $this->adminB = User::factory()->superAdmin()->for($this->organizationB)->create();
        $this->memberB = User::factory()->for($this->organizationB)->create(['name' => 'Beta Person']);
    }

    public function test_queries_only_return_users_of_the_viewers_organization(): void
    {
        $this->actingAs($this->adminA);

        $this->assertSame(1, User::query()->count());
        $this->assertNull(User::query()->find($this->memberB->id));
        $this->assertNull(User::query()->find($this->adminB->id));
    }

    public function test_the_users_list_never_shows_another_organizations_people(): void
    {
        Livewire::actingAs($this->adminA)
            ->test('pages::users.index')
            ->assertSee($this->adminA->name)
            ->assertDontSee('Beta Person')
            ->assertDontSee($this->adminB->name);
    }

    public function test_searching_cannot_surface_another_organizations_people(): void
    {
        Livewire::actingAs($this->adminA)
            ->test('pages::users.index')
            ->set('search', 'Beta Person')
            ->assertDontSee('Beta Person');
    }

    public function test_the_dashboard_counts_only_cover_the_viewers_organization(): void
    {
        User::factory()->count(3)->for($this->organizationB)->create();

        $this->actingAs($this->adminA)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Beta Person');

        $this->assertSame(
            1,
            Livewire::actingAs($this->adminA)
                ->test('pages::dashboard')
                ->get('stats')[0]['value'],
        );
    }

    public function test_editing_a_user_of_another_organization_by_id_fails(): void
    {
        $this->assertActionIsBlocked('editUser');
    }

    public function test_deleting_a_user_of_another_organization_by_id_fails(): void
    {
        $this->assertActionIsBlocked('confirmDelete');

        $this->assertDatabaseHas('users', ['id' => $this->memberB->id]);
    }

    public function test_deactivating_a_user_of_another_organization_by_id_fails(): void
    {
        $this->assertActionIsBlocked('toggleActive');

        $this->assertTrue($this->memberB->refresh()->is_active);
    }

    public function test_resetting_the_password_of_another_organizations_user_fails(): void
    {
        $originalPassword = $this->memberB->password;

        $this->assertActionIsBlocked('startPasswordReset');

        $this->assertSame($originalPassword, $this->memberB->refresh()->password);
    }

    /**
     * Assert that the given users action refuses an id from another organization.
     *
     * The global scope means the record is simply not found, so the action
     * aborts before any authorization or write can happen.
     */
    private function assertActionIsBlocked(string $action): void
    {
        try {
            Livewire::actingAs($this->adminA)
                ->test('pages::users.index')
                ->call($action, $this->memberB->id);

            $this->fail("Action [{$action}] should not reach a user of another organization.");
        } catch (ModelNotFoundException $exception) {
            $this->assertStringContainsString(User::class, $exception->getMessage());
        }
    }

    public function test_a_super_admin_cannot_edit_another_organizations_settings(): void
    {
        $this->assertFalse($this->adminA->can('update', $this->organizationB));
        $this->assertTrue($this->adminA->can('update', $this->organizationA));
    }

    public function test_organization_settings_only_ever_edit_the_viewers_own_organization(): void
    {
        $originalName = $this->organizationB->name;

        Livewire::actingAs($this->adminA)
            ->test('pages::settings.organization')
            ->set('name', 'Renamed By Alpha')
            ->set('primaryColor', '#1d4ed8')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Renamed By Alpha', $this->organizationA->refresh()->name);
        $this->assertSame($originalName, $this->organizationB->refresh()->name);
    }

    public function test_a_new_user_is_stamped_with_the_creators_organization(): void
    {
        $this->actingAs($this->adminA);

        $created = User::create([
            'name' => 'Stamped Person',
            'email' => 'stamped@example.test',
            'password' => 'secret-password',
        ]);

        $this->assertSame($this->organizationA->id, $created->organization_id);
    }

    public function test_tenancy_can_be_scoped_explicitly_for_jobs_and_seeders(): void
    {
        $count = Tenancy::actAs($this->organizationB, fn (): int => User::query()->count());

        $this->assertSame(2, $count);

        // Scoping is restored afterwards.
        $this->assertSame(0, Tenancy::currentOrganizationId() ?? 0);
    }

    public function test_the_policy_denies_cross_organization_access_even_without_the_global_scope(): void
    {
        // Simulates a future module that queries without the scope by mistake:
        // the policy is a second, independent line of defence.
        $memberB = User::withoutGlobalScopes()->find($this->memberB->id);

        $this->assertFalse($this->adminA->can('view', $memberB));
        $this->assertFalse($this->adminA->can('update', $memberB));
        $this->assertFalse($this->adminA->can('delete', $memberB));
        $this->assertFalse($this->adminA->can('resetPassword', $memberB));
    }
}
