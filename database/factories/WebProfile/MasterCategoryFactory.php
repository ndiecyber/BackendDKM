<?php

namespace Database\Factories\WebProfile;

use App\Models\WebProfile\MasterCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterCategory>
 */
class MasterCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['kategori', 'tipe_berita', 'label', 'status']),
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'icon_name' => 'fas fa-tag',
            'color' => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
