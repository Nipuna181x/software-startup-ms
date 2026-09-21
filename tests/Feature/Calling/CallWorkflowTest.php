<?php

namespace Tests\Feature\Calling;

use App\Enums\Calling\CallOutcome;
use App\Models\Calling\Business;
use App\Models\Calling\CallBusinessType;
use App\Models\Calling\CallCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CallWorkflowTest extends TestCase
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

    private function makeBusinessWithOnePhone(CallBusinessType $type, User $user, string $name = 'ABC Mobile'): Business
    {
        $business = Business::factory()->for($type, 'type')->for($user->organization)->create(['name' => $name]);
        $business->phones()->update(['number' => '0712345678', 'normalized_number' => '712345678', 'is_primary' => true]);

        return $business;
    }

    public function test_a_business_with_one_number_goes_straight_to_the_outcome_step(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);
        $business = $this->makeBusinessWithOnePhone($type, $user);
        $phone = $business->phones()->first();

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('startCall', $business->id)
            ->assertSet('loggingCallForBusinessId', $business->id)
            ->assertSet('loggingCallPhoneId', $phone->id);
    }

    public function test_a_business_with_multiple_numbers_requires_choosing_one(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);
        $business = $this->makeBusinessWithOnePhone($type, $user);
        $secondPhone = $business->phones()->create([
            'organization_id' => $user->organization_id,
            'number' => '0719999999',
            'is_primary' => false,
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('startCall', $business->id)
            ->assertSet('loggingCallPhoneId', null);

        $component->call('chooseCallPhone', $secondPhone->id)
            ->assertSet('loggingCallPhoneId', $secondPhone->id);
    }

    public function test_marking_not_answered_keeps_the_business_in_to_call_and_increments_attempts(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);
        $business = $this->makeBusinessWithOnePhone($type, $user);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('startCall', $business->id)
            ->call('logNotAnswered');

        $business = $business->fresh();

        $this->assertFalse($business->hasBeenAnswered());
        $this->assertSame(1, $business->attemptsSinceLastAnswer());
        $this->assertSame(1, $business->calls()->count());
        $this->assertSame(CallOutcome::NotAnswered, $business->calls()->first()->outcome);
    }

    public function test_repeated_not_answered_calls_each_create_a_new_history_row(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);
        $business = $this->makeBusinessWithOnePhone($type, $user);

        $component = Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type]);

        $component->call('startCall', $business->id)->call('logNotAnswered');
        $component->call('startCall', $business->id)->call('logNotAnswered');
        $component->call('startCall', $business->id)->call('logNotAnswered');

        $business = $business->fresh();

        $this->assertSame(3, $business->calls()->count());
        $this->assertSame(3, $business->attemptsSinceLastAnswer());
        $this->assertFalse($business->hasBeenAnswered());
    }

    public function test_marking_answered_requires_choosing_an_outcome(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);
        $business = $this->makeBusinessWithOnePhone($type, $user);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('startCall', $business->id)
            ->call('markAnswered')
            ->call('logAnswered')
            ->assertHasErrors('callOutcome');

        $this->assertDatabaseMissing('calls', ['business_id' => $business->id]);
    }

    public function test_marking_interested_moves_the_business_to_answered_with_the_right_status(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);
        $business = $this->makeBusinessWithOnePhone($type, $user);

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('startCall', $business->id)
            ->call('markAnswered')
            ->set('callOutcome', 'interested')
            ->set('callNote', 'Wants a follow-up call next week')
            ->set('callFollowUpAt', now()->addWeek()->toDateString())
            ->call('logAnswered')
            ->assertHasNoErrors();

        $business = $business->fresh();

        $this->assertTrue($business->hasBeenAnswered());
        $this->assertSame(CallOutcome::Interested, $business->currentStatus());

        $call = $business->calls()->first();
        $this->assertSame('Wants a follow-up call next week', $call->note);
        $this->assertNotNull($call->follow_up_at);
        $this->assertSame($user->id, $call->user_id);
        $this->assertNotNull($call->business_phone_id);
    }

    public function test_the_call_history_records_who_called_and_which_number(): void
    {
        $user = User::factory()->create(['name' => 'Amara Osei']);
        $type = $this->makeType($user);
        $business = $this->makeBusinessWithOnePhone($type, $user);
        $phone = $business->phones()->first();

        Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
            ->call('startCall', $business->id)
            ->call('logNotAnswered');

        $call = $business->fresh()->calls()->first();

        $this->assertSame($user->id, $call->user_id);
        $this->assertSame($phone->id, $call->business_phone_id);
        $this->assertNotNull($call->called_at);
    }

    public function test_the_status_can_be_changed_later_without_making_a_new_call(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);
        $business = $this->makeBusinessWithOnePhone($type, $user);

        $component = Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type]);

        $component->call('startCall', $business->id)
            ->call('markAnswered')
            ->set('callOutcome', 'pending')
            ->call('logAnswered');

        $this->assertSame(CallOutcome::Pending, $business->fresh()->currentStatus());

        $component->call('changeStatus', $business->id, 'rejected');

        $business = $business->fresh();

        $this->assertSame(CallOutcome::Rejected, $business->currentStatus());
        $this->assertSame(2, $business->calls()->count(), 'Changing status should add a new call, not overwrite the old one.');
    }

    public function test_the_answered_list_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);

        $pendingBiz = $this->makeBusinessWithOnePhone($type, $user, 'Pending Biz');
        $pendingBiz->calls()->create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'outcome' => CallOutcome::Pending,
            'called_at' => now(),
        ]);

        $rejectedBiz = $this->makeBusinessWithOnePhone($type, $user, 'Rejected Biz');
        $rejectedBiz->calls()->create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'outcome' => CallOutcome::Rejected,
            'called_at' => now(),
        ]);

        $component = Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type]);

        $component->assertSee('Pending Biz')->assertSee('Rejected Biz');

        $component->set('statusFilter', 'pending')
            ->assertSee('Pending Biz')
            ->assertDontSee('Rejected Biz');
    }

    public function test_the_counts_reflect_the_current_state_of_each_business(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);

        $toCall = $this->makeBusinessWithOnePhone($type, $user, 'To Call Biz');

        $pending = $this->makeBusinessWithOnePhone($type, $user, 'Pending Biz');
        $pending->calls()->create(['organization_id' => $user->organization_id, 'user_id' => $user->id, 'outcome' => CallOutcome::Pending, 'called_at' => now()]);

        $interested = $this->makeBusinessWithOnePhone($type, $user, 'Interested Biz');
        $interested->calls()->create(['organization_id' => $user->organization_id, 'user_id' => $user->id, 'outcome' => CallOutcome::Interested, 'called_at' => now()]);

        $component = Livewire::actingAs($user)
            ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type]);

        $counts = $component->get('counts');

        $this->assertSame(1, $counts['toCall']);
        $this->assertSame(1, $counts['pending']);
        $this->assertSame(1, $counts['interested']);
        $this->assertSame(0, $counts['rejected']);
    }

    public function test_a_user_cannot_log_a_call_for_another_organizations_business(): void
    {
        $user = User::factory()->create();
        $type = $this->makeType($user);
        $ownBusiness = $this->makeBusinessWithOnePhone($type, $user);

        $otherUser = User::factory()->create();
        $otherType = $this->makeType($otherUser);
        $otherBusiness = $this->makeBusinessWithOnePhone($otherType, $otherUser, 'Other Org Business');

        try {
            Livewire::actingAs($user)
                ->test('pages::calling.businesses.index', ['category' => $type->category, 'type' => $type])
                ->call('startCall', $otherBusiness->id);

            $this->fail('Logging a call for another organization\'s business should not succeed.');
        } catch (ModelNotFoundException $exception) {
            $this->assertStringContainsString(Business::class, $exception->getMessage());
        }

        $this->assertDatabaseMissing('calls', ['business_id' => $otherBusiness->id]);
    }
}
