<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Collection;

/**
 * 種目ごとの自己ベスト。
 *
 * 種目（exercise_id）が紐付いた記録だけを対象にし、
 * 重量 → 回数 → セット数 の順に比べて一番上の記録を自己ベストとする。
 * 重量なし（自重）は重量ありより下。同じ内容なら先に達成した記録を残す。
 */
class PersonalBestService
{
    /**
     * @return Collection<int, Workout> 種目の id 順。各 Workout には exercise を先読み済み
     */
    public function forUser(User $user): Collection
    {
        return $this->forUsers(collect([$user]))->get($user->id, collect());
    }

    /**
     * 複数ユーザーの自己ベストを1回のクエリでまとめて求める（ランキング用）
     *
     * @param  Collection<int, User>  $users
     * @return Collection<int, Collection<int, Workout>> ユーザー id => forUser() と同じ形。記録のないユーザーは含まない
     */
    public function forUsers(Collection $users): Collection
    {
        return Workout::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->whereNotNull('exercise_id')
            // 回数のない記録（重量・回数を記録する前のもの）は比べられないため対象外
            ->whereNotNull('reps')
            ->with('exercise')
            ->orderBy('performed_at')
            ->orderBy('id')
            ->get()
            ->groupBy('user_id')
            ->map(fn (Collection $workouts) => $workouts
                ->groupBy('exercise_id')
                ->map(fn (Collection $workouts) => $workouts->reduce(
                    fn (?Workout $best, Workout $workout) => $best === null || $this->isBetter($workout, $best) ? $workout : $best
                ))
                ->sortKeys()
                ->values());
    }

    private function isBetter(Workout $a, Workout $b): bool
    {
        // NULL（自重・未入力）は -1 として、値のある記録より下にする
        $score = fn (Workout $w) => [$w->weight ?? -1, $w->reps ?? -1, $w->sets ?? -1];

        return $score($a) > $score($b);
    }
}
