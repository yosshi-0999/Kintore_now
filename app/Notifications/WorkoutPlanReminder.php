<?php

namespace App\Notifications;

use App\Models\Muscle;
use App\Models\WorkoutPlan;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class WorkoutPlanReminder extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, array{muscle: Muscle, recovers_at: CarbonInterface}>  $restingMuscles  予定日時に休息中の筋肉（MuscleRestService::restingAmong の結果）
     */
    public function __construct(
        public WorkoutPlan $plan,
        public Collection $restingMuscles = new Collection,
    ) {}

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

        $mail = (new MailMessage)
            ->subject('【筋トレの予定】'.$plan->menu_name)
            ->greeting($notifiable->name.' さん')
            ->line('予定している筋トレの時間です。')
            ->line('日時：'.$plan->scheduled_at->format('Y/m/d H:i'))
            ->line('場所：'.$plan->place)
            ->line('メニュー：'.$plan->menu_name)
            ->line('筋肉：'.$plan->muscles->pluck('name')->join('、'));

        if ($this->restingMuscles->isNotEmpty()) {
            $mail->line('※ 次の筋肉は休息中です：'.$this->restingMuscles
                ->map(fn (array $item) => $item['muscle']->name.'（回復予定 '.$item['recovers_at']->format('Y/m/d H:i').'）')
                ->join('、'));
        }

        // リンクにはプランIDだけを入れる（トークンや個人情報は入れない）
        return $mail
            ->action('筋トレ内容を記録する', route('workouts.create', ['plan' => $plan->id]))
            ->salutation('Kintore now');
    }
}
