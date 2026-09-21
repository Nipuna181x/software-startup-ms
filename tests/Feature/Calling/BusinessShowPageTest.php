<?php

namespace Tests\Feature\Calling;

use App\Enums\Calling\CallOutcome;
use App\Models\Calling\Business;
use App\Models\Calling\CallCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessShowPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeBusiness(User $user): Business
    {
        $category = CallCategory::factory()->for($user->organization)->create();
        $type = $category->businessTypes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Phone Shops',
        ]);
        $business = Business::factory()->for($type, 'type')->for($user->organization)->create(['name' => 'ABC Mobile']);
        $business->phones()->update(['number' => '0712345678', 'normalized_number' => '712345678', 'is_primary' => true]);

        return $business;
    }

    public function test_the_show_page_displays_the_call_history_newest_first(): void
    {
        $user = User::factory()->create();
        $business = $this->makeBusiness($user);

        $business->calls()->create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'outcome' => CallOutcome::NotAnswered,
            'called_at' => now()->subDays(2),
        ]);
        $business->calls()->create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'outcome' => CallOutcome::Pending,
            'note' => 'Will decide next week',
            'called_at' => now()->subDay(),
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::calling.businesses.show', [
                'category' => $business->type->category,
                'type' => $business->type,
                'business' => $business,
            ]);

        $history = $component->get('callHistory');

        $this->assertSame(2, $history->count());
        $this->assertSame(CallOutcome::Pending, $history->first()->outcome, 'History should be newest first.');
        $this->assertSame(CallOutcome::NotAnswered, $history->last()->outcome);

        $component->assertSee('Will decide next week');
    }

    public function test_logging_a_call_from_the_show_page_updates_the_status_and_history(): void
    {
        $user = User::factory()->create();
        $business = $this->makeBusiness($user);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.show', [
                'category' => $business->type->category,
                'type' => $business->type,
                'business' => $business,
            ])
            ->call('startCall', $business->id)
            ->call('markAnswered')
            ->set('callOutcome', 'interested')
            ->call('logAnswered')
            ->assertHasNoErrors();

        $business = $business->fresh();

        $this->assertSame(CallOutcome::Interested, $business->currentStatus());
        $this->assertSame(1, $business->calls()->count());
    }

    public function test_the_status_badge_only_shows_once_the_business_has_been_answered(): void
    {
        $user = User::factory()->create();
        $business = $this->makeBusiness($user);

        $this->actingAs($user)
            ->get(route('calling.businesses.show', [$business->type->category, $business->type, $business]))
            ->assertOk()
            ->assertDontSee('Pending')
            ->assertDontSee('Interested')
            ->assertDontSee('Rejected');

        $business->calls()->create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'outcome' => CallOutcome::Interested,
            'called_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('calling.businesses.show', [$business->type->category, $business->type, $business]))
            ->assertOk()
            ->assertSee('Interested');
    }
}
