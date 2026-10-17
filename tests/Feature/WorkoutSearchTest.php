<?php

namespace Tests\Feature;

use App\Models\Muscle;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class WorkoutSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Muscle $chest;

    private Muscle $back;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-10-17 12:00:00');
        $this->owner = User::factory()->create();
        $this->chest = Muscle::factory()->create(['name' => '大胸筋']);
        $this->back = Muscle::factory()->create(['name' => '広背筋']);

        $this->record('フラットベンチ', $this->chest, '2026-10-01 10:00:00');
        $this->record('インクラインベンチ', $this->chest, '2026-10-10 10:00:00');
        $this->record('懸垂', $this->back, '2026-10-15 10:00:00');
    }

    private function record(string $menu, Muscle $muscle, string $performedAt, ?User $user = null): Workout
    {
        $workout = Workout::factory()->for($user ?? $this->owner)->create([
            'menu_name' => $menu,
            'performed_at' => $performedAt,
        ]);
        $workout->muscles()->attach($muscle);

        return $workout;
    }

    private function page()
    {
        $this->actingAs($this->owner);

        return Volt::test('users.show', ['user' => $this->owner]);
    }

    public function test_records_can_be_searched_by_menu_name(): void
    {
        $this->page()
            ->set('keyword', 'ベンチ')
            ->assertHasNoErrors()
            ->assertSee('フラットベンチ')
            ->assertSee('インクラインベンチ')
            ->assertDontSee('懸垂')
            ->assertSee('2 件見つかりました');
    }

    public function test_percent_in_keyword_is_not_a_wildcard(): void
    {
        $this->page()
            ->set('keyword', '%')
            ->assertSee('条件に合う記録がありません。');
    }

    public function test_records_can_be_searched_by_muscle(): void
    {
        $this->page()
            ->set('muscle_id', (string) $this->back->id)
            ->assertSee('懸垂')
            ->assertDontSee('フラットベンチ');
    }

    public function test_records_can_be_searched_by_period_including_both_ends(): void
    {
        $this->page()
            ->set('from', '2026-10-10')
            ->set('to', '2026-10-15')
            ->assertSee('インクラインベンチ')
            ->assertSee('懸垂')
            ->assertDontSee('フラットベンチ');
    }

    public function test_conditions_can_be_combined_and_cleared(): void
    {
        $this->page()
            ->set('keyword', 'ベンチ')
            ->set('from', '2026-10-05')
            ->assertSee('インクラインベンチ')
            ->assertDontSee('フラットベンチ')
            ->call('clearSearch')
            ->assertSet('keyword', '')
            ->assertSee('フラットベンチ')
            ->assertSee('懸垂');
    }

    public function test_invalid_conditions_show_errors_and_are_ignored(): void
    {
        $this->page()
            ->set('muscle_id', '999999')
            ->assertHasErrors(['muscle_id' => 'exists'])
            ->assertSee('フラットベンチ')
            ->assertSee('懸垂');

        $this->page()
            ->set('from', '2026-10-15')
            ->set('to', '2026-10-01')
            ->assertHasErrors(['to' => 'after_or_equal']);
    }

    public function test_older_records_are_shown_on_the_next_page(): void
    {
        foreach (range(1, 20) as $i) {
            $this->record("新しい記録{$i}", $this->chest, '2026-10-16 10:00:00');
        }

        $this->page()
            ->assertSee('新しい記録1')
            ->assertDontSee('フラットベンチ')
            ->call('gotoPage', 2)
            ->assertSee('フラットベンチ')
            ->assertSee('懸垂');
    }

    public function test_changing_conditions_returns_to_the_first_page(): void
    {
        foreach (range(1, 20) as $i) {
            $this->record("新しい記録{$i}", $this->chest, '2026-10-16 10:00:00');
        }

        $this->page()
            ->call('gotoPage', 2)
            ->set('keyword', '懸垂')
            ->assertSee('懸垂')
            ->assertSee('1 件見つかりました');
    }

    public function test_other_users_records_are_not_included(): void
    {
        $this->record('他人のベンチ', $this->chest, '2026-10-12 10:00:00', User::factory()->create());

        $this->page()
            ->set('keyword', 'ベンチ')
            ->assertDontSee('他人のベンチ')
            ->assertSee('2 件見つかりました');
    }

    public function test_permitted_user_can_search(): void
    {
        $viewer = User::factory()->create();
        $this->owner->viewers()->attach($viewer);

        $this->actingAs($viewer);

        Volt::test('users.show', ['user' => $this->owner])
            ->set('keyword', '懸垂')
            ->assertSee('懸垂')
            ->assertDontSee('フラットベンチ');
    }

    public function test_user_without_permission_sees_neither_search_nor_records(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('users.show', ['user' => $this->owner, 'keyword' => 'ベンチ']))
            ->assertOk()
            ->assertDontSee('フラットベンチ')
            ->assertDontSee('メニュー名')
            ->assertSee('許可されたユーザーのみ閲覧できます');
    }
}
