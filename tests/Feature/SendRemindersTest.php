<?php

namespace Tests\Feature;

use App\Models\Muscle;
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

    public function test_reminder_command_is_scheduled_every_minute(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('reminders:send')
            ->assertSuccessful();
    }
}
