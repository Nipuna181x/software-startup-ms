<?php

namespace Database\Factories\Calling;

use App\Models\Calling\Business;
use App\Models\Calling\BusinessScreenshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessScreenshot>
 */
class BusinessScreenshotFactory extends Factory
{
    protected $model = BusinessScreenshot::class;

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
            'path' => 'business-screenshots/'.fake()->uuid().'.png',
        ];
    }
}
