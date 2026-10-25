<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\Muscle;
use App\Models\User;
use App\Models\Workout;
use App\Services\MuscleRestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MuscleRestServiceTest extends TestCase
{
    use RefreshDatabase;

    private function recordWorkout(User $user, array $muscles, string $performedAt, int $restDays): Workout
    {
        $workout = Workout::factory()->for($user)->create([
            'performed_at' => $performedAt,
            'rest_days' => $restDays,
        ]);
        $workout->muscles()->attach(collect($muscles)->pluck('id'));

        return $workout;
    }

    public function test_muscle_is_resting_until_performed_at_plus_rest_days(): void
    {
        $this->travelTo('2026-10-15 12:00:00');
        $user = User::factory()->create();
        $chest = Muscle::factory()->create();
        $back = Muscle::factory()->create();

        $this->recordWorkout($user, [$chest], '2026-10-14 10:00:00', 2);
        $this->recordWorkout($user, [$back], '2026-10-13 10:00:00', 2);

        $resting = app(MuscleRestService::class)->restingMuscles($user);

        $this->assertSame([$chest->id], $resting->keys()->all());
        $this->assertSame('2026-10-16 10:00:00', $resting[$chest->id]['recovers_at']->format('Y-m-d H:i:s'));
    }

    public function test_latest_recovery_is_used_when_muscle_is_trained_twice(): void
    {
        $this->travelTo('2026-10-15 12:00:00');
        $user = User::factory()->create();
        $chest = Muscle::factory()->create();

        $this->recordWorkout($user, [$chest], '2026-10-14 10:00:00', 3);
        $this->recordWorkout($user, [$chest], '2026-10-15 09:00:00', 1);

        $resting = app(MuscleRestService::class)->restingMuscles($user);

        $this->assertSame('2026-10-17 10:00:00', $resting[$chest->id]['recovers_at']->format('Y-m-d H:i:s'));
    }

    public function test_resting_can_be_checked_at_a_future_time(): void
    {
        $this->travelTo('2026-10-15 12:00:00');
        $user = User::factory()->create();
        $chest = Muscle::factory()->create();

        $this->recordWorkout($user, [$chest], '2026-10-15 10:00:00', 2);
        $service = app(MuscleRestService::class);

        $this->assertTrue($service->restingAmong($user, [$chest->id], now()->addDay())->has($chest->id));
        $this->assertTrue($service->restingAmong($user, [$chest->id], now()->addDays(3))->isEmpty());
    }

    public function test_other_users_workouts_are_ignored(): void
    {
        $user = User::factory()->create();
        $chest = Muscle::factory()->create();

        $this->recordWorkout(User::factory()->create(), [$chest], now()->subHour()->toDateTimeString(), 2);

        $this->assertTrue(app(MuscleRestService::class)->restingMuscles($user)->isEmpty());
    }

    public function test_available_muscles_and_suggestions_exclude_resting_muscles(): void
    {
        $user = User::factory()->create();
        $chest = Muscle::factory()->create();
        $legs = Muscle::factory()->create();
        Exercise::factory()->for($chest)->create(['name' => 'ベンチプレス']);
        Exercise::factory()->for($legs)->create(['name' => 'スクワット']);

        $this->recordWorkout($user, [$chest], now()->subHour()->toDateTimeString(), 2);
        $overview = app(MuscleRestService::class)->overview($user);

        $this->assertSame([$chest->id], $overview['resting']->keys()->all());
        $this->assertSame([$legs->id], $overview['available']->pluck('id')->all());
        $this->assertSame(['スクワット'], $overview['suggestions']->pluck('name')->all());
    }
}
