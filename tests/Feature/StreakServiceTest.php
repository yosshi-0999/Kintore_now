<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workout;
use App\Services\StreakService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StreakServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Carbon $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->now = Carbon::parse('2026-10-28 12:00');
    }

    private function recordAt(string $datetime): void
    {
        Workout::factory()->for($this->user)->create(['performed_at' => Carbon::parse($datetime)]);
    }

    private function streak(): array
    {
        return app(StreakService::class)->forUser($this->user, $this->now);
    }

    public function test_no_records_means_zero(): void
    {
        $this->assertSame(['current' => 0, 'longest' => 0, 'total_days' => 0, 'last_date' => null], $this->streak());
    }

    public function test_consecutive_days_up_to_today_are_counted(): void
    {
        $this->recordAt('2026-10-26 08:00');
        $this->recordAt('2026-10-27 20:00');
        $this->recordAt('2026-10-28 07:00');

        $streak = $this->streak();

        $this->assertSame(3, $streak['current']);
        $this->assertSame(3, $streak['longest']);
        $this->assertSame(3, $streak['total_days']);
        $this->assertSame('2026-10-28', $streak['last_date']->format('Y-m-d'));
    }

    public function test_streak_continues_if_last_record_was_yesterday(): void
    {
        $this->recordAt('2026-10-26 08:00');
        $this->recordAt('2026-10-27 08:00');

        $this->assertSame(2, $this->streak()['current']);
    }

    public function test_streak_is_broken_after_a_missed_day(): void
    {
        $this->recordAt('2026-10-25 08:00');
        $this->recordAt('2026-10-26 08:00');

        $streak = $this->streak();

        $this->assertSame(0, $streak['current']);
        $this->assertSame(2, $streak['longest']);
    }

    public function test_multiple_records_on_the_same_day_count_once(): void
    {
        $this->recordAt('2026-10-28 07:00');
        $this->recordAt('2026-10-28 09:00');
        $this->recordAt('2026-10-28 11:00');

        $streak = $this->streak();

        $this->assertSame(1, $streak['current']);
        $this->assertSame(1, $streak['total_days']);
    }

    public function test_longest_streak_is_kept_from_the_past(): void
    {
        foreach (['10-01', '10-02', '10-03', '10-04', '10-10', '10-27', '10-28'] as $day) {
            $this->recordAt("2026-{$day} 08:00");
        }

        $streak = $this->streak();

        $this->assertSame(2, $streak['current']);
        $this->assertSame(4, $streak['longest']);
        $this->assertSame(7, $streak['total_days']);
    }

    public function test_future_records_are_not_counted(): void
    {
        $this->recordAt('2026-10-28 08:00');
        $this->recordAt('2026-10-28 18:00');
        $this->recordAt('2026-10-29 08:00');

        $streak = $this->streak();

        $this->assertSame(1, $streak['current']);
        $this->assertSame(1, $streak['total_days']);
    }

    public function test_other_users_records_are_not_counted(): void
    {
        Workout::factory()->for(User::factory())->create(['performed_at' => Carbon::parse('2026-10-28 08:00')]);

        $this->assertSame(0, $this->streak()['total_days']);
    }

    public function test_streak_tab_is_shown_on_own_page(): void
    {
        Workout::factory()->for($this->user)->create(['performed_at' => now()]);

        $this->actingAs($this->user)->get(route('users.show', $this->user))
            ->assertOk()
            ->assertSee('継続日数')
            ->assertSee('連続記録')
            ->assertSee('最長連続');
    }

    public function test_permitted_user_can_see_streak(): void
    {
        Workout::factory()->for($this->user)->create(['performed_at' => now()]);
        $viewer = User::factory()->create();
        $this->user->viewers()->attach($viewer);

        $this->actingAs($viewer)->get(route('users.show', $this->user))
            ->assertOk()
            ->assertSee('連続記録');
    }

    public function test_user_without_permission_cannot_see_streak(): void
    {
        Workout::factory()->for($this->user)->create(['performed_at' => now()]);

        $this->actingAs(User::factory()->create())->get(route('users.show', $this->user))
            ->assertOk()
            ->assertDontSee('連続記録')
            ->assertSee('許可されたユーザーのみ閲覧できます');
    }
}
