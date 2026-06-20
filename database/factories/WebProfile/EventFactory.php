<?php

namespace Database\Factories\WebProfile;

use App\Models\WebProfile\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
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
            'description' => fake()->paragraph(),
            'date' => fake()->date(),
            'time' => fake()->time(),
            'type' => fake()->randomElement(['Kajian', 'Pendidikan', 'Sosial']),
            'is_active' => true,
            'category' => 'Umum',
            'badge' => 'Segera',
            'image' => '/storage/events/default.jpg',
            'location' => fake()->address(),
            'author' => fake()->name(),
            'content' => fake()->paragraphs(3, true),
            'hits' => fake()->numberBetween(0, 100),
        ];
    }
}
