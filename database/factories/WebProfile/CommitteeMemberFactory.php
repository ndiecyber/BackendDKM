<?php

namespace Database\Factories\WebProfile;

use App\Models\WebProfile\CommitteeMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommitteeMember>
 */
class CommitteeMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group' => fake()->randomElement(['dewan_penasihat', 'pengurus_harian', 'divisi']),
            'division_id' => null,
            'name' => fake()->name(),
            'role' => fake()->jobTitle(),
            'image' => '/storage/committee/default.jpg',
            'is_leader' => fake()->boolean(20),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
