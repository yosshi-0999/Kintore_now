<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Services\WeightProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Exercise $bench;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->bench = Exercise::factory()->create(['name' => 'ベンチプレス']);
    }

    private function record(?float $weight, string $performedAt, array $attributes = []): Workout
    {
        return Workout::factory()->for($this->user)->create([
            'exercise_id' => $this->bench->id,
            'weight' => $weight,
            'reps' => 5,
            'sets' => 3,
            'performed_at' => $performedAt,
            ...$attributes,
        ]);
    }

    public function test_points_are_max_weight_per_day_in_date_order(): void
    {
        $this->record(70, '2026-10-12 10:00:00');
        $this->record(60, '2026-10-10 10:00:00');
        $this->record(65, '2026-10-10 19:00:00');

        $series = app(WeightProgressService::class)->forUser($this->user)->sole();

        $this->assertSame($this->bench->id, $series['exercise_id']);
        $this->assertSame('ベンチプレス', $series['name']);
        $this->assertSame([
            ['date' => '2026-10-10', 'weight' => 65.0],
            ['date' => '2026-10-12', 'weight' => 70.0],
        ], $series['points']);
    }

    public function test_series_are_split_by_exercise_in_id_order(): void
    {
        $squat = Exercise::factory()->create(['name' => 'スクワット']);
        $this->record(100, '2026-10-10 10:00:00', ['exercise_id' => $squat->id]);
        $this->record(60, '2026-10-11 10:00:00');

        $names = app(WeightProgressService::class)->forUser($this->user)->pluck('name')->all();

        $this->assertSame(['ベンチプレス', 'スクワット'], $names);
    }

    public function test_bodyweight_records_without_exercise_and_other_users_are_excluded(): void
    {
        $this->record(null, '2026-10-10 10:00:00');
        $this->record(60, '2026-10-10 10:00:00', ['exercise_id' => null]);
        $this->record(80, '2026-10-10 10:00:00', ['user_id' => User::factory()->create()->id]);

        $this->assertTrue(app(WeightProgressService::class)->forUser($this->user)->isEmpty());
    }
}
