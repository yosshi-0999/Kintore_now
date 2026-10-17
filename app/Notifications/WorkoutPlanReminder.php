<?php

namespace App\Notifications;

use App\Models\WorkoutPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkoutPlanReminder extends Notification
{
    use Queueable;

    public function __construct(public WorkoutPlan $plan) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->plan;

        // リンクにはプランIDだけを入れる（トークンや個人情報は入れない）
        return (new MailMessage)
            ->subject('【筋トレの予定】'.$plan->menu_name)
            ->greeting($notifiable->name.' さん')
            ->line('予定している筋トレの時間です。')
            ->line('日時：'.$plan->scheduled_at->format('Y/m/d H:i'))
            ->line('場所：'.$plan->place)
            ->line('メニュー：'.$plan->menu_name)
            ->line('筋肉：'.$plan->muscles->pluck('name')->join('、'))
            ->action('筋トレ内容を記録する', route('workouts.create', ['plan' => $plan->id]))
            ->salutation('Kintore now');
    }
}
