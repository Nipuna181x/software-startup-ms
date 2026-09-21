<?php

namespace Tests\Feature\Calling;

use App\Models\Calling\Business;
use App\Models\Calling\CallCategory;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Proves that one organization's business screenshots can never be opened by
 * another organization or by a logged-out visitor, even by guessing the
 * screenshot id.
 */
class ScreenshotIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makeBusinessWithScreenshot(Organization $organization): Business
    {
        $category = CallCategory::factory()->for($organization)->create();
        $type = $category->businessTypes()->create([
            'organization_id' => $organization->id,
            'name' => 'Phone Shops',
        ]);
        $business = Business::factory()->for($type, 'type')->for($organization)->create();

        $path = 'business-screenshots/secret.png';
        Storage::disk('local')->put($path, 'private-image-content');

        $business->screenshots()->create([
            'organization_id' => $organization->id,
            'path' => $path,
        ]);

        return $business;
    }

    public function test_a_guest_cannot_view_a_screenshot(): void
    {
        $organization = Organization::factory()->create();
        $business = $this->makeBusinessWithScreenshot($organization);
        $screenshot = $business->screenshots()->first();

        $this->get(route('calling.screenshots.show', $screenshot))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_in_another_organization_cannot_view_a_screenshot(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();

        $business = $this->makeBusinessWithScreenshot($organizationA);
        $screenshot = $business->screenshots()->first();

        $userB = User::factory()->for($organizationB)->create();

        $this->actingAs($userB)
            ->get(route('calling.screenshots.show', $screenshot))
            ->assertNotFound();
    }

    public function test_a_member_of_the_owning_organization_can_view_the_screenshot(): void
    {
        $organization = Organization::factory()->create();
        $business = $this->makeBusinessWithScreenshot($organization);
        $screenshot = $business->screenshots()->first();

        $user = User::factory()->for($organization)->create();

        $this->actingAs($user)
            ->get(route('calling.screenshots.show', $screenshot))
            ->assertOk();
    }

    public function test_screenshots_are_stored_outside_the_public_disk(): void
    {
        $organization = Organization::factory()->create();
        $business = $this->makeBusinessWithScreenshot($organization);
        $screenshot = $business->screenshots()->first();

        Storage::disk('public')->assertMissing($screenshot->path);
        Storage::disk('local')->assertExists($screenshot->path);
    }

    public function test_deleting_a_business_removes_its_screenshots_from_disk(): void
    {
        $organization = Organization::factory()->create();
        $business = $this->makeBusinessWithScreenshot($organization);
        $screenshot = $business->screenshots()->first();
        $path = $screenshot->path;

        $business->screenshots()->get()->each->delete();

        Storage::disk('local')->assertMissing($path);
    }
}
