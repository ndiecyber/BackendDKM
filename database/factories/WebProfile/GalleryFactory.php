<?php

namespace Database\Factories\WebProfile;

use App\Models\WebProfile\Gallery;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_path' => '/storage/galleries/default.jpg',
            'caption' => fake()->sentence(),
            'is_active' => true,
            'subcaption' => fake()->sentence(),
            'tag' => fake()->word(),
            'category' => fake()->randomElement(['Arsitektur', 'Kegiatan']),
            'icon_name' => 'fas fa-image',
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
