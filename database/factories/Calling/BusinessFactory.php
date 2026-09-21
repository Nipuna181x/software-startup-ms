<?php

namespace Database\Factories\Calling;

use App\Models\Calling\Business;
use App\Models\Calling\CallBusinessType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = CallBusinessType::factory()->create();

        return [
            'organization_id' => $type->organization_id,
            'call_business_type_id' => $type->id,
            'name' => fake()->unique()->company(),
            'address' => fake()->optional()->address(),
            'owner_name' => fake()->optional()->name(),
            'website' => fake()->optional()->url(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Attach a primary phone number after creating the business.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Business $business): void {
            if ($business->phones()->doesntExist()) {
                $business->phones()->create([
                    'organization_id' => $business->organization_id,
                    'number' => fake()->numerify('07########'),
                    'is_primary' => true,
                ]);
            }
        });
    }
}
