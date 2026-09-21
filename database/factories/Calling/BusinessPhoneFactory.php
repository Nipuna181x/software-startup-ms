<?php

namespace Database\Factories\Calling;

use App\Models\Calling\Business;
use App\Models\Calling\BusinessPhone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessPhone>
 */
class BusinessPhoneFactory extends Factory
{
    protected $model = BusinessPhone::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $business = Business::factory()->create();

        return [
            'organization_id' => $business->organization_id,
            'business_id' => $business->id,
            'number' => fake()->numerify('07########'),
            'is_primary' => false,
        ];
    }
}
