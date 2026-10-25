<?php

namespace App\Actions;

use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Support\Facades\DB;

class SaveWorkoutPlan
{
    /**
     * 通知プランを作成・更新する。
     * 予定日時の休息チェックは呼び出し側で済ませておくこと。
     *
     * @param  array{scheduled_at: string, place: string, menu_name: string, muscle_ids: array<int|string>}  $data
     */
    public function __invoke(User $user, array $data, ?WorkoutPlan $plan = null): WorkoutPlan
    {
        return DB::transaction(function () use ($user, $data, $plan) {
            $plan ??= new WorkoutPlan(['user_id' => $user->id]);

            $plan->fill([
                'scheduled_at' => $data['scheduled_at'],
                'place' => $data['place'],
                'menu_name' => $data['menu_name'],
            ]);

            // 予定日時を変えたら、もう一度通知する
            if ($plan->isDirty('scheduled_at')) {
                $plan->sent_at = null;
            }

            $plan->save();
            $plan->muscles()->sync($data['muscle_ids']);

            return $plan;
        });
    }
}
