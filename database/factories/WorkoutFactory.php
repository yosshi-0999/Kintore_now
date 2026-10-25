<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workout>
 */
class WorkoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'performed_at' => now()->subDays(10),
            'menu_name' => fake()->word(),
            'weight' => 60,
            'reps' => 10,
            'sets' => 3,
            'rest_days' => 2,
        ];
    }
}
