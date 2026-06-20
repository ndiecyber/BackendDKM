<?php

namespace Database\Factories\WebProfile;

use App\Models\WebProfile\CtaProgram;
use App\Models\WebProfile\CtaSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CtaProgram>
 */
class CtaProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cta_setting_id' => CtaSetting::factory(),
            'name' => fake()->words(3, true),
            'progress' => fake()->numberBetween(0, 100),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
