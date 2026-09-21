<?php

namespace Tests\Feature\Calling;

use App\Models\Calling\Business;
use App\Models\Calling\CallCategory;
use App\Models\Organization;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DuplicatePhoneCheckTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function equivalentNumberFormats(): array
    {
        return [
            'plain vs plus94' => ['0712345678', '+94 71 234 5678'],
            'dashes vs no dashes' => ['071-234-5678', '0712345678'],
            'spaces vs 94 prefix' => ['071 234 5678', '94712345678'],
        ];
    }

    #[DataProvider('equivalentNumberFormats')]
    public function test_it_detects_the_same_number_written_differently(string $existing, string $incoming): void
    {
        $user = User::factory()->create();
        $category = CallCategory::factory()->for($user->organization)->create();
        $type = $category->businessTypes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Phone Shops',
        ]);
        $business = Business::factory()->for($type, 'type')->for($user->organization)->create(['name' => 'Existing Shop']);
        $business->phones()->update(['number' => $existing, 'normalized_number' => PhoneNumber::normalize($existing)]);

        $found = Business::findByPhoneNumber($incoming);

        $this->assertNotNull($found);
        $this->assertSame($business->id, $found->id);
    }

    public function test_the_form_warns_when_a_duplicate_number_is_entered(): void
    {
        $user = User::factory()->create();
        $category = CallCategory::factory()->for($user->organization)->create();
        $type = $category->businessTypes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Phone Shops',
        ]);
        $existing = Business::factory()->for($type, 'type')->for($user->organization)->create(['name' => 'Existing Shop']);
        $existing->phones()->update(['number' => '0712345678', 'normalized_number' => '712345678']);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $category, 'type' => $type])
            ->call('addBusiness')
            ->set('phones.0.number', '+94 71 234 5678')
            ->assertSee('Existing Shop');
    }

    public function test_editing_a_business_does_not_warn_about_its_own_number(): void
    {
        $user = User::factory()->create();
        $category = CallCategory::factory()->for($user->organization)->create();
        $type = $category->businessTypes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Phone Shops',
        ]);
        $business = Business::factory()->for($type, 'type')->for($user->organization)->create();
        $business->phones()->update(['number' => '0712345678', 'normalized_number' => '712345678']);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $category, 'type' => $type])
            ->call('editBusiness', $business->id)
            ->set('phones.0.number', '0712345678')
            ->assertDontSee('already belongs to');
    }

    public function test_the_duplicate_check_is_scoped_to_the_organization(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();

        $categoryA = CallCategory::factory()->for($organizationA)->create();
        $typeA = $categoryA->businessTypes()->create(['organization_id' => $organizationA->id, 'name' => 'Phone Shops']);
        $businessA = Business::factory()->for($typeA, 'type')->for($organizationA)->create();
        $businessA->phones()->update(['number' => '0712345678', 'normalized_number' => '712345678']);

        $userB = User::factory()->for($organizationB)->create();

        $this->actingAs($userB);

        $found = Business::findByPhoneNumber('0712345678');

        $this->assertNull($found);
    }

    public function test_saving_a_duplicate_number_does_not_block_the_save(): void
    {
        $user = User::factory()->create();
        $category = CallCategory::factory()->for($user->organization)->create();
        $type = $category->businessTypes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Phone Shops',
        ]);
        $existing = Business::factory()->for($type, 'type')->for($user->organization)->create();
        $existing->phones()->update(['number' => '0712345678', 'normalized_number' => '712345678']);

        // The duplicate check warns but does not prevent saving; the Super
        // Admin decides whether it really is a duplicate.
        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $category, 'type' => $type])
            ->call('addBusiness')
            ->set('name', 'Second Entry')
            ->set('phones.0.number', '0712345678')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('businesses', ['name' => 'Second Entry']);
    }
}
