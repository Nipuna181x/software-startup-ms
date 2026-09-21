<?php

namespace Database\Factories\Calling;

use App\Models\Calling\CallCategory;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CallCategory>
 */
class CallCategoryFactory extends Factory
{
    protected $model = CallCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
