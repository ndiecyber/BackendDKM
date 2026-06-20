<?php

namespace Database\Factories\WebProfile;

use App\Models\WebProfile\CtaSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CtaSetting>
 */
class CtaSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'subtitle' => fake()->paragraph(),
            'quote' => fake()->sentence(),
            'quote_source' => fake()->name(),
            'total_donors' => fake()->numberBetween(10, 1000),
            'slider_images' => ['/storage/cta/1.jpg', '/storage/cta/2.jpg'],
        ];
    }
}
