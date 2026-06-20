<?php

namespace Database\Factories\WebProfile;

use App\Models\WebProfile\CommitteeDivision;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CommitteeDivision>
 */
class CommitteeDivisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'slug' => Str::slug($name),
            'name' => ucwords($name),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
