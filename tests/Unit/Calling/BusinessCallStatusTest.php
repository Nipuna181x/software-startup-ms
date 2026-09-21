<?php

namespace Tests\Unit\Calling;

use App\Enums\Calling\CallOutcome;
use App\Models\Calling\Business;
use App\Models\Calling\CallCategory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Exercises Business::currentStatus() and attemptsSinceLastAnswer() against
 * realistic call histories.
 *
 * currentStatus() is built on a "latest of many" relation constrained to
 * answered outcomes; naively combining whereIn() with latestOfMany() applies
 * the outcome filter after finding the globally-latest call by date, so if
 * the true latest call is unanswered the whole relation silently returns
 * null even when an earlier answered call exists. These tests pin the
 * correct behavior (the filter must be baked into the aggregate subquery).
 */
class BusinessCallStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeBusiness(): Business
    {
        $user = User::factory()->create();
        $category = CallCategory::factory()->for($user->organization)->create();
        $type = $category->businessTypes()->create([
            'organization_id' => $user->organization_id,
            'name' => 'Phone Shops',
        ]);
        $business = Business::factory()->for($type, 'type')->for($user->organization)->create();
        $business->phones()->create([
            'organization_id' => $user->organization_id,
            'number' => '0712345678',
            'is_primary' => true,
        ]);

        $this->actingAs($user);

        return $business;
    }

    public function test_a_business_with_no_calls_has_no_status(): void
    {
        $business = $this->makeBusiness();

        $this->assertNull($business->currentStatus());
        $this->assertFalse($business->hasBeenAnswered());
    }

    public function test_unanswered_attempts_do_not_give_the_business_a_status(): void
    {
        $business = $this->makeBusiness();

        $business->calls()->create($this->makeCall(CallOutcome::NotAnswered, now()->subHours(2)));
        $business->calls()->create($this->makeCall(CallOutcome::NotAnswered, now()->subHours(1)));

        $business = $business->fresh();

        $this->assertNull($business->currentStatus());
        $this->assertSame(2, $business->attemptsSinceLastAnswer());
    }

    public function test_the_status_comes_from_the_latest_answered_call(): void
    {
        $business = $this->makeBusiness();

        $business->calls()->create($this->makeCall(CallOutcome::Pending, now()->subHours(2)));
        $business->calls()->create($this->makeCall(CallOutcome::Rejected, now()->subHours(1)));

        $business = $business->fresh();

        $this->assertSame(CallOutcome::Rejected, $business->currentStatus());
    }

    public function test_an_unanswered_attempt_after_being_answered_keeps_the_last_answered_status(): void
    {
        $business = $this->makeBusiness();

        $business->calls()->create($this->makeCall(CallOutcome::Pending, now()->subHours(2)));
        $business->calls()->create($this->makeCall(CallOutcome::NotAnswered, now()->subHours(1)));

        $business = $business->fresh();

        // The most recent call overall is unanswered, but the business must
        // still report its last real answer as the current status.
        $this->assertSame(CallOutcome::Pending, $business->currentStatus());
    }

    public function test_attempts_since_last_answer_only_counts_calls_after_the_answer(): void
    {
        $business = $this->makeBusiness();

        $business->calls()->create($this->makeCall(CallOutcome::NotAnswered, now()->subHours(4)));
        $business->calls()->create($this->makeCall(CallOutcome::NotAnswered, now()->subHours(3)));
        $business->calls()->create($this->makeCall(CallOutcome::Pending, now()->subHours(2)));
        $business->calls()->create($this->makeCall(CallOutcome::NotAnswered, now()->subHours(1)));

        $business = $business->fresh();

        $this->assertSame(CallOutcome::Pending, $business->currentStatus());
        $this->assertSame(1, $business->attemptsSinceLastAnswer());
    }

    public function test_changing_status_later_is_recorded_as_a_new_call_not_an_overwrite(): void
    {
        $business = $this->makeBusiness();

        $business->calls()->create($this->makeCall(CallOutcome::Pending, now()->subDay()));
        $business->calls()->create($this->makeCall(CallOutcome::Rejected, now()));

        $business = $business->fresh();

        $this->assertSame(CallOutcome::Rejected, $business->currentStatus());
        $this->assertSame(2, $business->calls()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function makeCall(CallOutcome $outcome, CarbonInterface $calledAt): array
    {
        return [
            'organization_id' => auth()->user()->organization_id,
            'user_id' => auth()->id(),
            'outcome' => $outcome,
            'called_at' => $calledAt,
        ];
    }
}
