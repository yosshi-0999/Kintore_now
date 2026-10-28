<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * 継続日数（F9）。
 *
 * 記録が1件以上ある日を「筋トレした日」とし、日付は config('app.timezone')（Asia/Tokyo）で区切る。
 * 基準日時より後（未来）の記録は数えない。
 *
 * - current: 基準日まで続いている連続日数。今日まだ記録していなくても、昨日まで続いていれば途切れていない扱いにする
 * - longest: これまでで最も長い連続日数
 * - total_days: 筋トレした日の合計
 * - last_date: 最後に筋トレした日（記録がなければ null）
 */
class StreakService
{
    /**
     * @return array{current: int, longest: int, total_days: int, last_date: ?CarbonImmutable}
     */
    public function forUser(User $user, ?CarbonInterface $now = null): array
    {
        $now = CarbonImmutable::instance($now ?? now());

        $days = $this->trainingDays($user, $now);

        if ($days->isEmpty()) {
            return ['current' => 0, 'longest' => 0, 'total_days' => 0, 'last_date' => null];
        }

        $lastDate = CarbonImmutable::parse($days->last());

        return [
            'current' => $this->currentStreak($days, $lastDate, $now),
            'longest' => $this->longestStreak($days),
            'total_days' => $days->count(),
            'last_date' => $lastDate,
        ];
    }

    /**
     * 筋トレした日（Y-m-d）を古い順に重複なしで返す
     *
     * @return Collection<int, string>
     */
    private function trainingDays(User $user, CarbonImmutable $now): Collection
    {
        return $user->workouts()
            ->where('performed_at', '<=', $now)
            ->orderBy('performed_at')
            ->pluck('performed_at')
            ->map(fn (CarbonInterface $performedAt) => $performedAt->format('Y-m-d'))
            ->unique()
            ->values();
    }

    /**
     * @param  Collection<int, string>  $days
     */
    private function currentStreak(Collection $days, CarbonImmutable $lastDate, CarbonImmutable $now): int
    {
        // 最後に筋トレした日が昨日より前なら、もう途切れている
        if ($lastDate->lt($now->startOfDay()->subDay())) {
            return 0;
        }

        $set = $days->flip();
        $streak = 0;

        for ($day = $lastDate; $set->has($day->format('Y-m-d')); $day = $day->subDay()) {
            $streak++;
        }

        return $streak;
    }

    /**
     * @param  Collection<int, string>  $days
     */
    private function longestStreak(Collection $days): int
    {
        $longest = 0;
        $run = 0;
        $previous = null;

        foreach ($days as $day) {
            $date = CarbonImmutable::parse($day);
            $run = $previous !== null && $previous->addDay()->isSameDay($date) ? $run + 1 : 1;
            $longest = max($longest, $run);
            $previous = $date;
        }

        return $longest;
    }
}
