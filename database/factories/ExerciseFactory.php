<?php

namespace Database\Factories;

use App\Models\Exercise;
use App\Models\Muscle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Exercise>
 */
class ExerciseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'muscle_id' => Muscle::factory(),
            'default_rest_days' => fake()->numberBetween(1, 3),
        ];
    }
}
