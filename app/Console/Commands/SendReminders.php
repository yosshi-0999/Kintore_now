<?php

namespace App\Console\Commands;

use App\Models\WorkoutPlan;
use App\Notifications\WorkoutPlanReminder;
use Illuminate\Console\Command;
use Throwable;

class SendReminders extends Command
{
    protected $signature = 'reminders:send';

    protected $description = '予定日時になった通知プランのメールを送信する';

    public function handle(): int
    {
        $plans = WorkoutPlan::query()->due()->with(['user', 'muscles'])->get();
        $sent = 0;

        foreach ($plans as $plan) {
            // 先に sent_at を記録して、同時実行による二重送信を防ぐ
            $claimed = WorkoutPlan::query()
                ->whereKey($plan->id)
                ->whereNull('sent_at')
                ->update(['sent_at' => now()]);

            if ($claimed === 0) {
                continue;
            }

            try {
                $plan->user->notify(new WorkoutPlanReminder($plan));
                $sent++;
            } catch (Throwable $e) {
                // 送れなかったら次回また送れるよう元に戻す
                WorkoutPlan::query()->whereKey($plan->id)->update(['sent_at' => null]);
                report($e);
            }
        }

        $this->info("{$sent} 件の通知を送信しました。");

        return self::SUCCESS;
    }
}
