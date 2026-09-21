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
        ];
    }
}
