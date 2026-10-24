<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Collection;

/**
 * 種目ごとの最高重量の推移（グラフ用）。
 *
 * 自己ベストと同じく種目（exercise_id）が紐付いた記録を対象にし、
 * 日ごとにその日の最高重量を1点とする。重量なし（自重）の記録は対象外。
 */
class WeightProgressService
{
    /**
     * @return Collection<int, array{exercise_id: int, name: string, points: list<array{date: string, weight: float}>}> 種目の id 順
     */
    public function forUser(User $user): Collection
    {
        return Workout::query()
            ->whereBelongsTo($user)
            ->whereNotNull('exercise_id')
            ->whereNotNull('weight')
            ->with('exercise')
            ->orderBy('performed_at')
            ->get()
            ->groupBy('exercise_id')
            ->sortKeys()
            ->map(fn (Collection $workouts) => [
                'exercise_id' => $workouts->first()->exercise_id,
                'name' => $workouts->first()->exercise->name,
                'points' => $workouts
                    ->groupBy(fn (Workout $workout) => $workout->performed_at->toDateString())
                    ->map(fn (Collection $day, string $date) => ['date' => $date, 'weight' => $day->max('weight')])
                    ->values()
                    ->all(),
            ])
            ->values();
    }
}
