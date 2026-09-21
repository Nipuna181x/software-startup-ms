<?php

namespace Tests\Feature\Calling;

use App\Models\Calling\Business;
use App\Models\Calling\CallBusinessType;
use App\Models\Calling\CallCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeType(User $user): CallBusinessType
    {
        $category = CallCategory::factory()->for($user->organization)->create();

        return $category->businessTypes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Phone Shops',
        ]);
    }

    public function test_any_member_can_view_the_businesses_page(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);

        $this->actingAs($user)
            ->get(route('calling.businesses.index', [$type->category, $type]))
            ->assertOk()
            ->assertSee('Phone Shops');
    }

    public function test_any_member_can_add_a_business_with_a_phone_number(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('addBusiness')
            ->set('name', 'ABC Mobile')
            ->set('phones.0.number', '071 234 5678')
            ->set('address', '123 Main St')
            ->set('ownerName', 'Jane Doe')
            ->set('website', 'https://abcmobile.test')
            ->set('notes', 'Sells refurbished phones')
            ->call('save')
            ->assertHasNoErrors();

        $business = Business::firstWhere('name', 'ABC Mobile');

        $this->assertNotNull($business);
        $this->assertSame('123 Main St', $business->address);
        $this->assertSame('Jane Doe', $business->owner_name);
        $this->assertSame(1, $business->phones()->count());
        $this->assertSame('712345678', $business->phones()->first()->normalized_number);
        $this->assertTrue($business->phones()->first()->is_primary);
    }

    public function test_a_business_requires_at_least_one_phone_number(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('addBusiness')
            ->set('name', 'No Phone Co')
            ->set('phones.0.number', '')
            ->call('save')
            ->assertHasErrors('phones.0.number');

        $this->assertDatabaseMissing('businesses', ['name' => 'No Phone Co']);
    }

    public function test_a_business_cannot_be_saved_with_no_phone_rows_at_all(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('addBusiness')
            ->set('name', 'No Rows Co')
            ->set('phones', [])
            ->call('save')
            ->assertHasErrors('phones');

        $this->assertDatabaseMissing('businesses', ['name' => 'No Rows Co']);
    }

    public function test_a_business_can_have_multiple_phone_numbers_with_one_primary(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('addBusiness')
            ->set('name', 'Multi Phone Co')
            ->set('phones.0.number', '0712345678')
            ->call('addPhoneField')
            ->set('phones.1.number', '0719999999')
            ->call('makePrimary', 1)
            ->call('save')
            ->assertHasNoErrors();

        $business = Business::firstWhere('name', 'Multi Phone Co');

        $this->assertSame(2, $business->phones()->count());
        $this->assertTrue($business->phones()->where('normalized_number', '719999999')->first()->is_primary);
        $this->assertFalse($business->phones()->where('normalized_number', '712345678')->first()->is_primary);
    }

    public function test_any_member_can_edit_a_business(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);
        $business = Business::factory()->for($type, 'type')->for($user->organization)->create(['name' => 'Old Name']);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('editBusiness', $business->id)
            ->set('name', 'New Name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('New Name', $business->refresh()->name);
    }

    public function test_a_regular_user_can_delete_a_business_only_if_super_admin(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);
        $business = Business::factory()->for($type, 'type')->for($user->organization)->create();

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('confirmDelete', $business->id)
            ->assertForbidden();

        $this->assertDatabaseHas('businesses', ['id' => $business->id]);
    }

    public function test_a_super_admin_can_delete_a_business(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $type = $this->makeType($admin);
        $business = Business::factory()->for($type, 'type')->for($admin->organization)->create();

        Livewire::actingAs($admin)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('confirmDelete', $business->id)
            ->call('deleteBusiness');

        $this->assertDatabaseMissing('businesses', ['id' => $business->id]);
    }

    public function test_the_businesses_list_can_be_searched_by_name_and_number(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);

        $matchByName = Business::factory()->for($type, 'type')->for($user->organization)->create(['name' => 'Findable Shop']);
        $other = Business::factory()->for($type, 'type')->for($user->organization)->create(['name' => 'Other Shop']);
        $other->phones()->update(['number' => '0711112222', 'normalized_number' => '711112222']);

        $component = Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->set('search', 'Findable');

        $component->assertSee('Findable Shop')->assertDontSee('Other Shop');

        $component->set('search', '711112222')
            ->assertSee('Other Shop')
            ->assertDontSee('Findable Shop');
    }

    public function test_screenshots_can_be_uploaded_and_are_stored_on_the_private_disk(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $type = $this->makeType($user);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('addBusiness')
            ->set('name', 'Screenshot Co')
            ->set('phones.0.number', '0712223333')
            ->set('newScreenshots', [UploadedFile::fake()->image('shop.png')])
            ->call('save')
            ->assertHasNoErrors();

        $business = Business::firstWhere('name', 'Screenshot Co');

        $this->assertSame(1, $business->screenshots()->count());

        $screenshot = $business->screenshots()->first();
        Storage::disk('local')->assertExists($screenshot->path);
    }

    public function test_an_svg_screenshot_is_rejected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $type = $this->makeType($user);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('addBusiness')
            ->set('name', 'Svg Co')
            ->set('phones.0.number', '0712223333')
            ->set('newScreenshots', [UploadedFile::fake()->create('shot.svg', 10, 'image/svg+xml')])
            ->call('save')
            ->assertHasErrors('newScreenshots.0');

        $this->assertDatabaseMissing('businesses', ['name' => 'Svg Co']);
    }

    public function test_removing_an_existing_screenshot_deletes_it_from_disk(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $type = $this->makeType($user);
        $business = Business::factory()->for($type, 'type')->for($user->organization)->create();
        $screenshot = $business->screenshots()->create([
            'organization_id' => $user->organization_id,
            'path' => 'business-screenshots/existing.png',
        ]);
        Storage::disk('local')->put($screenshot->path, 'fake-image-content');

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('editBusiness', $business->id)
            ->call('removeExistingScreenshot', $screenshot->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('business_screenshots', ['id' => $screenshot->id]);
        Storage::disk('local')->assertMissing('business-screenshots/existing.png');
    }
}
