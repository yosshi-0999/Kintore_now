<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Services\PersonalBestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalBestServiceTest extends TestCase
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

    private function record(?float $weight, ?int $reps, ?int $sets, array $attributes = []): Workout
    {
        return Workout::factory()->for($this->user)->create([
            'exercise_id' => $this->bench->id,
            'weight' => $weight,
            'reps' => $reps,
            'sets' => $sets,
            ...$attributes,
        ]);
    }

    private function best(): Workout
    {
        return app(PersonalBestService::class)->forUser($this->user)->sole();
    }

    public function test_heaviest_weight_wins_even_with_fewer_reps_and_sets(): void
    {
        $this->record(60, 12, 5);
        $heaviest = $this->record(80, 1, 1);

        $this->assertTrue($this->best()->is($heaviest));
    }

    public function test_reps_decide_when_weight_is_the_same(): void
    {
        $this->record(60, 8, 5);
        $moreReps = $this->record(60, 10, 1);

        $this->assertTrue($this->best()->is($moreReps));
    }

    public function test_sets_decide_when_weight_and_reps_are_the_same(): void
    {
        $this->record(60, 10, 3);
        $moreSets = $this->record(60, 10, 4);

        $this->assertTrue($this->best()->is($moreSets));
    }

    public function test_bodyweight_is_below_any_weighted_record(): void
    {
        $this->record(null, 50, 5);
        $weighted = $this->record(2.5, 1, 1);

        $this->assertTrue($this->best()->is($weighted));
    }

    public function test_bodyweight_records_are_compared_by_reps(): void
    {
        $this->record(null, 10, 3);
        $moreReps = $this->record(null, 15, 3);

        $this->assertTrue($this->best()->is($moreReps));
    }

    public function test_first_achieved_record_is_kept_when_tied(): void
    {
        $first = $this->record(60, 10, 3, ['performed_at' => now()->subDays(5)]);
        $this->record(60, 10, 3, ['performed_at' => now()->subDay()]);

        $this->assertTrue($this->best()->is($first));
    }

    public function test_one_best_per_exercise_in_exercise_order(): void
    {
        $squat = Exercise::factory()->create(['name' => 'スクワット']);
        $this->record(60, 10, 3);
        $this->record(100, 5, 3, ['exercise_id' => $squat->id]);

        $bests = app(PersonalBestService::class)->forUser($this->user);

        $this->assertSame([$this->bench->id, $squat->id], $bests->pluck('exercise_id')->all());
        $this->assertSame('スクワット', $bests[1]->exercise->name);
    }

    public function test_records_without_reps_are_ignored(): void
    {
        // 重量・回数を記録する前の記録
        $this->record(null, null, null);

        $this->assertTrue(app(PersonalBestService::class)->forUser($this->user)->isEmpty());
    }

    public function test_exercise_name_must_match_exactly(): void
    {
        $this->assertTrue(Exercise::findByExactName(' ベンチプレス ')->is($this->bench));
        $this->assertNull(Exercise::findByExactName('ペンチプレス'));
        $this->assertNull(Exercise::findByExactName('ベンチ'));
        $this->assertNull(Exercise::findByExactName(''));
    }

    public function test_records_without_exercise_and_other_users_records_are_ignored(): void
    {
        $this->record(60, 10, 3);
        $this->record(200, 10, 3, ['exercise_id' => null]);
        Workout::factory()->for(User::factory()->create())->create([
            'exercise_id' => $this->bench->id,
            'weight' => 300,
        ]);

        $this->assertSame(60.0, $this->best()->weight);
    }
}
