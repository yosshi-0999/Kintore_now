<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\Muscle;
use App\Models\User;
use App\Models\Workout;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * 休息中の筋肉の判定。
 *
 * 休息中のテーブルは持たず、workouts から計算する。
 * 「performed_at + rest_days」が基準日時より後なら、その筋肉は休息中。
 * フォーム・提案・通知プランはすべてこのクラスを使うこと。
 */
class MuscleRestService
{
    /**
     * 休憩日数の最大値（フォームの選択肢と合わせる）
     */
    public const MAX_REST_DAYS = 7;

    /**
     * 休息中の筋肉と回復予定日時。
     *
     * @return Collection<int, array{muscle: Muscle, recovers_at: CarbonInterface}> muscle_id をキーにした一覧
     */
    public function restingMuscles(User $user, ?CarbonInterface $at = null): Collection
    {
        $at ??= now();

        $workouts = Workout::query()
            ->whereBelongsTo($user)
            ->where('performed_at', '>', $at->copy()->subDays(self::MAX_REST_DAYS))
            ->where('performed_at', '<=', $at)
            ->with('muscles')
            ->get();

        $resting = collect();

        foreach ($workouts as $workout) {
            $recoversAt = $workout->performed_at->copy()->addDays($workout->rest_days);

            if ($recoversAt->lte($at)) {
                continue;
            }

            foreach ($workout->muscles as $muscle) {
                $current = $resting->get($muscle->id);

                if ($current === null || $recoversAt->gt($current['recovers_at'])) {
                    $resting->put($muscle->id, ['muscle' => $muscle, 'recovers_at' => $recoversAt]);
                }
            }
        }

        return $resting->sortBy(fn (array $item) => $item['muscle']->id);
    }

    /**
     * 指定した筋肉のうち、休息中のもの。
     *
     * @param  array<int|string>  $muscleIds
     * @return Collection<int, array{muscle: Muscle, recovers_at: CarbonInterface}>
     */
    public function restingAmong(User $user, array $muscleIds, ?CarbonInterface $at = null): Collection
    {
        $ids = array_map('intval', $muscleIds);

        return $this->restingMuscles($user, $at)->only($ids);
    }

    /**
     * ユーザーページ用に、休ませる筋肉・使える筋肉・おすすめの種目をまとめて求める。
     * 休息判定（restingMuscles）は1回だけ行い、3つで使い回す。
     *
     * @return array{
     *     resting: Collection<int, array{muscle: Muscle, recovers_at: CarbonInterface}>,
     *     available: Collection<int, Muscle>,
     *     suggestions: Collection<int, Exercise>,
     * }
     */
    public function overview(User $user, ?CarbonInterface $at = null): array
    {
        $resting = $this->restingMuscles($user, $at);
        $restingIds = $resting->keys();

        return [
            'resting' => $resting,
            // 使える筋肉（休息中でない筋肉。記録のない筋肉も含む）
            'available' => Muscle::query()->whereNotIn('id', $restingIds)->orderBy('id')->get(),
            // 使える筋肉に効果的な種目（おすすめ）
            'suggestions' => Exercise::query()
                ->whereNotIn('muscle_id', $restingIds)
                ->with('muscle')
                ->orderBy('muscle_id')
                ->get(),
        ];
    }
}
