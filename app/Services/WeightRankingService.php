<?php

namespace App\Services;

use App\Models\Exercise;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * 種目ごとの重量ランキング。
 *
 * 参加者は、見る人本人と、見る人に閲覧を許可しているユーザー（UserPolicy::viewRecords を通す）。
 * 各参加者の自己ベスト（PersonalBestService）の重量で並べる。自重の自己ベストは対象外。
 * 同じ重量は同じ順位にし、先に達成した人を上に表示する。
 */
class WeightRankingService
{
    public function __construct(private PersonalBestService $personalBests) {}

    /**
     * @return Collection<int, array{exercise: Exercise, rows: list<array{rank: int, user: User, workout: Workout}>}> 種目 id => ランキング。種目の id 順
     */
    public function forViewer(User $viewer): Collection
    {
        $participants = $viewer->viewableOwners()->get()
            ->prepend($viewer)
            ->filter(fn (User $user) => Gate::forUser($viewer)->allows('viewRecords', $user))
            ->keyBy('id');

        return $this->personalBests->forUsers($participants)
            ->flatten(1)
            ->filter(fn (Workout $best) => $best->weight !== null)
            ->groupBy('exercise_id')
            ->sortKeys()
            ->map(fn (Collection $bests) => [
                'exercise' => $bests->first()->exercise,
                'rows' => $this->rank(
                    $bests->sortBy([['weight', 'desc'], ['performed_at', 'asc'], ['id', 'asc']])->values(),
                    $participants,
                ),
            ]);
    }

    /**
     * @param  Collection<int, Workout>  $sorted  重量の重い順
     * @param  Collection<int, User>  $participants
     * @return list<array{rank: int, user: User, workout: Workout}>
     */
    private function rank(Collection $sorted, Collection $participants): array
    {
        $rows = [];

        foreach ($sorted as $index => $workout) {
            $previous = $rows[$index - 1] ?? null;

            $rows[] = [
                // 同じ重量なら前の人と同じ順位（1位, 1位, 3位 …）
                'rank' => $previous !== null && $previous['workout']->weight === $workout->weight ? $previous['rank'] : $index + 1,
                'user' => $participants->get($workout->user_id),
                'workout' => $workout,
            ];
        }

        return $rows;
    }
}
