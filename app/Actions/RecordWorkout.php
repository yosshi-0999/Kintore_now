<?php

namespace App\Actions;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Facades\DB;

class RecordWorkout
{
    /**
     * 筋トレ記録を保存する。
     * 休息中の筋肉の確認は呼び出し側（フォーム）で済ませておくこと。
     *
     * @param  array{performed_at: string, menu_name: string, weight?: string|float|null, reps: int|string, sets: int|string, rest_days: int, muscle_ids: array<int|string>}  $data
     */
    public function __invoke(User $user, array $data): Workout
    {
        return DB::transaction(function () use ($user, $data) {
            $workout = $user->workouts()->create([
                'performed_at' => $data['performed_at'],
                'menu_name' => $data['menu_name'],
                // 自重トレーニングなど、重量が空なら NULL で保存する
                'weight' => filled($data['weight'] ?? null) ? $data['weight'] : null,
                'reps' => $data['reps'],
                'sets' => $data['sets'],
                'rest_days' => $data['rest_days'],
            ]);

            $workout->muscles()->sync($data['muscle_ids']);

            return $workout;
        });
    }
}
