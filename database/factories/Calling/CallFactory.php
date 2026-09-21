<?php

namespace Database\Factories\Calling;

use App\Enums\Calling\CallOutcome;
use App\Models\Calling\Business;
use App\Models\Calling\Call;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Call>
 */
class CallFactory extends Factory
{
    protected $model = Call::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $business = Business::factory()->create();
        $user = User::factory()->for($business->organization)->create();

        return [
            'organization_id' => $business->organization_id,
            'business_id' => $business->id,
            'business_phone_id' => $business->phones()->first()?->id,
            'user_id' => $user->id,
            'outcome' => CallOutcome::NotAnswered,
            'note' => null,
            'follow_up_at' => null,
            'called_at' => now(),
        ];
    }

    /**
     * Indicate the call went unanswered.
     */
    public function notAnswered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'outcome' => CallOutcome::NotAnswered,
        ]);
    }

    /**
     * Indicate the call was answered with the given outcome.
     */
    public function answered(CallOutcome $outcome = CallOutcome::Pending): static
    {
        return $this->state(fn (array $attributes): array => [
            'outcome' => $outcome,
        ]);
    }
}
