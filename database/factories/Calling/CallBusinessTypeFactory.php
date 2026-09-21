<?php

namespace Database\Factories\Calling;

use App\Models\Calling\CallBusinessType;
use App\Models\Calling\CallCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CallBusinessType>
 */
class CallBusinessTypeFactory extends Factory
{
    protected $model = CallBusinessType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = CallCategory::factory()->create();

        return [
            'organization_id' => $category->organization_id,
            'call_category_id' => $category->id,
            'name' => fake()->unique()->words(2, true),
        ];
    }
}
