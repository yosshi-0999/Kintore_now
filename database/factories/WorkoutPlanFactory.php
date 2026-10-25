<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkoutPlan>
 */
class WorkoutPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scheduled_at' => now()->addDay(),
            'place' => 'ジム',
            'menu_name' => fake()->word(),
            'is_enabled' => true,
            'sent_at' => null,
        ];
    }
}
