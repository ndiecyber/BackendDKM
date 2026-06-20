<?php

namespace Database\Factories\WebProfile;

use App\Models\WebProfile\WhatsappContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsappContact>
 */
class WhatsappContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'number' => fake()->phoneNumber(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
