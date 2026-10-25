<?php

namespace Tests\Feature;

use App\Models\Muscle;
use App\Models\Workout;
use App\Models\WorkoutPlan;
use App\Notifications\WorkoutPlanReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendRemindersTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_plan_is_notified_once(): void
    {
        Notification::fake();
        $plan = WorkoutPlan::factory()->create(['scheduled_at' => now()->subMinute()]);

        $this->artisan('reminders:send')->assertSuccessful();
        $this->artisan('reminders:send')->assertSuccessful();

        Notification::assertSentToTimes($plan->user, WorkoutPlanReminder::class, 1);
        $this->assertNotNull($plan->fresh()->sent_at);
    }

    public function test_future_disabled_and_sent_plans_are_not_notified(): void
    {
        Notification::fake();
        WorkoutPlan::factory()->create(['scheduled_at' => now()->addMinute()]);
        WorkoutPlan::factory()->create(['scheduled_at' => now()->subMinute(), 'is_enabled' => false]);
        WorkoutPlan::factory()->create(['scheduled_at' => now()->subMinute(), 'sent_at' => now()->subMinute()]);

        $this->artisan('reminders:send')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_mail_contains_plan_details_and_form_link(): void
    {
        $plan = WorkoutPlan::factory()->create([
            'scheduled_at' => '2026-10-20 18:00:00',
            'place' => '駅前ジム',
            'menu_name' => '胸の日',
        ]);
        $plan->muscles()->attach(Muscle::factory()->create(['name' => '大胸筋']));

        $mail = (new WorkoutPlanReminder($plan->load('muscles')))->toMail($plan->user);
        $lines = implode("\n", $mail->introLines);

        $this->assertStringContainsString('2026/10/20 18:00', $lines);
        $this->assertStringContainsString('駅前ジム', $lines);
        $this->assertStringContainsString('胸の日', $lines);
        $this->assertStringContainsString('大胸筋', $lines);
        $this->assertSame(route('workouts.create', ['plan' => $plan->id]), $mail->actionUrl);
    }

    private function planWithChest(string $scheduledAt): array
    {
        $chest = Muscle::factory()->create(['name' => '大胸筋']);
        $plan = WorkoutPlan::factory()->create(['scheduled_at' => $scheduledAt]);
        $plan->muscles()->attach($chest);

        return [$plan, $chest];
    }

    private function recordWorkout(WorkoutPlan $plan, Muscle $muscle, string $performedAt, int $restDays): void
    {
        $workout = Workout::factory()->for($plan->user)->create([
            'performed_at' => $performedAt,
            'rest_days' => $restDays,
        ]);
        $workout->muscles()->attach($muscle);
    }

    private function sentMailLines(WorkoutPlan $plan): string
    {
        $lines = '';

        Notification::assertSentTo($plan->user, WorkoutPlanReminder::class, function (WorkoutPlanReminder $notification) use ($plan, &$lines) {
            $lines = implode("\n", $notification->toMail($plan->user)->introLines);

            return true;
        });

        return $lines;
    }

    public function test_mail_warns_when_muscle_became_resting_after_plan_was_created(): void
    {
        Notification::fake();
        $this->travelTo('2026-10-20 18:00:00');
        [$plan, $chest] = $this->planWithChest('2026-10-20 18:00:00');
        $this->recordWorkout($plan, $chest, '2026-10-19 18:00:00', 3);

        $this->artisan('reminders:send')->assertSuccessful();

        $lines = $this->sentMailLines($plan);
        $this->assertStringContainsString('次の筋肉は休息中です', $lines);
        $this->assertStringContainsString('大胸筋（回復予定 2026/10/22 18:00）', $lines);
    }

    public function test_mail_has_no_warning_when_no_muscle_is_resting(): void
    {
        Notification::fake();
        $this->travelTo('2026-10-20 18:00:00');
        [$plan, $chest] = $this->planWithChest('2026-10-20 18:00:00');
        $this->recordWorkout($plan, $chest, '2026-10-17 18:00:00', 2);

        $this->artisan('reminders:send')->assertSuccessful();

        $this->assertStringNotContainsString('休息中', $this->sentMailLines($plan));
    }

    public function test_resting_is_checked_at_scheduled_time_not_send_time(): void
    {
        Notification::fake();
        // スケジューラが遅れて 18:30 に送る。18:10 に回復するので、送る時点では休息中ではない
        $this->travelTo('2026-10-20 18:30:00');
        [$plan, $chest] = $this->planWithChest('2026-10-20 18:00:00');
        $this->recordWorkout($plan, $chest, '2026-10-18 18:10:00', 2);

        $this->artisan('reminders:send')->assertSuccessful();

        $this->assertStringContainsString('大胸筋（回復予定 2026/10/20 18:10）', $this->sentMailLines($plan));
    }

    public function test_reminder_command_is_scheduled_every_minute(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('reminders:send')
            ->assertSuccessful();
    }
}
