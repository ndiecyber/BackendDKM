<?php

namespace Database\Factories\WebProfile;

use App\Models\WebProfile\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'icon' => 'fas fa-mosque',
            'is_active' => true,
            'category' => fake()->randomElement(['Ibadah', 'Pendidikan', 'Sosial']),
            'badge' => 'Tersedia',
            'bg_image' => '/storage/services/bg.jpg',
            'details' => ['schedule' => 'Setiap Hari', 'supervisor' => fake()->name()],
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
