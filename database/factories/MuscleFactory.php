<?php

namespace Database\Factories;

use App\Models\Muscle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Muscle>
 */
class MuscleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().'筋',
        ];
    }
}
