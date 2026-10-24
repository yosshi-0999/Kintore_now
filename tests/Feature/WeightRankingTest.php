<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use App\Services\PersonalBestService;
use App\Services\WeightRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class WeightRankingTest extends TestCase
{
    use RefreshDatabase;

    private User $viewer;

    private Exercise $bench;

    protected function setUp(): void
    {
        parent::setUp();

        $this->viewer = User::factory()->create(['name' => 'Viewer']);
        $this->bench = Exercise::factory()->create(['name' => 'ベンチプレス']);
    }

    private function record(User $user, ?float $weight, array $attributes = []): Workout
    {
        return Workout::factory()->for($user)->create([
            'exercise_id' => $this->bench->id,
            'weight' => $weight,
            'reps' => 5,
            'sets' => 3,
            ...$attributes,
        ]);
    }

    /**
     * viewer に閲覧を許可したユーザー
     */
    private function owner(string $name): User
    {
        $owner = User::factory()->create(['name' => $name]);
        $owner->viewers()->attach($this->viewer);

        return $owner;
    }

    /**
     * @return list<array{0: int, 1: string, 2: float}> [順位, 名前, 重量]
     */
    private function rows(?Exercise $exercise = null): array
    {
        $ranking = app(WeightRankingService::class)->forViewer($this->viewer)->get(($exercise ?? $this->bench)->id);

        return collect($ranking['rows'] ?? [])
            ->map(fn (array $row) => [$row['rank'], $row['user']->name, $row['workout']->weight])
            ->all();
    }

    public function test_viewer_and_users_who_granted_permission_are_ranked_by_personal_best_weight(): void
    {
        $this->record($this->viewer, 60);
        $this->record($this->viewer, 70);
        $this->record($this->owner('Strong'), 100);
        $this->record($this->owner('Middle'), 80);

        $this->assertSame([[1, 'Strong', 100.0], [2, 'Middle', 80.0], [3, 'Viewer', 70.0]], $this->rows());
    }

    public function test_users_without_permission_are_not_ranked(): void
    {
        $this->record($this->viewer, 60);
        $this->record(User::factory()->create(['name' => 'Stranger']), 200);
        // viewer が見せているだけの相手も対象外
        $grantedByViewer = User::factory()->create(['name' => 'GrantedByViewer']);
        $this->viewer->viewers()->attach($grantedByViewer);
        $this->record($grantedByViewer, 150);

        $this->assertSame([[1, 'Viewer', 60.0]], $this->rows());
    }

    public function test_same_weight_shares_rank_and_earlier_achievement_comes_first(): void
    {
        $this->record($this->owner('Later'), 80, ['performed_at' => '2026-10-20 10:00:00']);
        $this->record($this->owner('Earlier'), 80, ['performed_at' => '2026-10-10 10:00:00']);
        $this->record($this->viewer, 60);

        $this->assertSame([[1, 'Earlier', 80.0], [1, 'Later', 80.0], [3, 'Viewer', 60.0]], $this->rows());
    }

    public function test_bodyweight_bests_and_records_without_exercise_are_excluded(): void
    {
        $pullUp = Exercise::factory()->create(['name' => '懸垂']);
        $this->record($this->viewer, null, ['exercise_id' => $pullUp->id]);
        $this->record($this->viewer, 60, ['exercise_id' => null]);

        $this->assertTrue(app(WeightRankingService::class)->forViewer($this->viewer)->isEmpty());
    }

    public function test_rankings_are_split_by_exercise(): void
    {
        $squat = Exercise::factory()->create(['name' => 'スクワット']);
        $owner = $this->owner('Owner');
        $this->record($this->viewer, 60);
        $this->record($owner, 50);
        $this->record($owner, 120, ['exercise_id' => $squat->id]);

        $this->assertSame([[1, 'Viewer', 60.0], [2, 'Owner', 50.0]], $this->rows());
        $this->assertSame([[1, 'Owner', 120.0]], $this->rows($squat));
    }

    public function test_personal_bests_for_users_match_for_user(): void
    {
        $owner = $this->owner('Owner');
        $this->record($this->viewer, 60);
        $this->record($owner, 80);

        $bests = app(PersonalBestService::class)->forUsers(collect([$this->viewer, $owner]));

        foreach ([$this->viewer, $owner] as $user) {
            $this->assertSame(
                app(PersonalBestService::class)->forUser($user)->pluck('id')->all(),
                $bests->get($user->id)->pluck('id')->all(),
            );
        }
    }

    public function test_page_shows_ranking_and_can_switch_exercise(): void
    {
        $squat = Exercise::factory()->create(['name' => 'スクワット']);
        $owner = $this->owner('Owner');
        $this->record($this->viewer, 60);
        $this->record($owner, 120, ['exercise_id' => $squat->id]);

        $this->actingAs($this->viewer);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSeeInOrder(['ベンチプレス', 'スクワット', '1位', 'Viewer', '60kg']);

        Volt::test('ranking.index')
            ->set('exercise_id', (string) $squat->id)
            ->assertSee('120kg')
            ->assertDontSee('60kg');
    }

    public function test_page_shows_message_without_records_and_sidebar_has_link(): void
    {
        $this->actingAs($this->viewer)
            ->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('まだランキングに表示できる記録がありません')
            ->assertSee(route('ranking.index'), false);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('ranking.index'))->assertRedirect(route('login'));
    }
}
