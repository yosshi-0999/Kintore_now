<?php

use App\Services\WeightRankingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component {
    #[Url(as: 'exercise')]
    public string $exercise_id = '';

    public function with(): array
    {
        $rankings = app(WeightRankingService::class)->forViewer(Auth::user());

        return [
            'rankings' => $rankings,
            // 指定がない・不正な種目なら先頭の種目を表示する
            'selected' => $rankings->get((int) $this->exercise_id) ?? $rankings->first(),
        ];
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-6">
    <div>
        <flux:heading size="xl" level="1">ランキング</flux:heading>
        <flux:text class="mt-1">あなたと、あなたに記録の閲覧を許可しているユーザーの、種目ごとの自己ベスト重量のランキングです（自重は含みません）。</flux:text>
    </div>

    @if ($selected === null)
        <flux:text>まだランキングに表示できる記録がありません。「種目から入力」で種目を選び、重量を入れて記録すると表示されます。</flux:text>
    @else
        <flux:select wire:model.live="exercise_id" label="種目" class="max-w-xs">
            @foreach ($rankings as $ranking)
                <flux:select.option value="{{ $ranking['exercise']->id }}">{{ $ranking['exercise']->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
            @foreach ($selected['rows'] as $row)
                <div
                    wire:key="rank-{{ $selected['exercise']->id }}-{{ $row['user']->id }}"
                    @class([
                        'flex items-center gap-4 px-4 py-3',
                        'bg-amber-50 dark:bg-amber-950' => $row['user']->is(auth()->user()),
                    ])
                >
                    <span @class([
                        'w-10 text-center text-lg font-bold',
                        'text-amber-500' => $row['rank'] === 1,
                        'text-zinc-400' => $row['rank'] !== 1,
                    ])>{{ $row['rank'] }}位</span>
                    <div class="flex-1">
                        <flux:link :href="route('users.show', $row['user'])" wire:navigate>{{ $row['user']->name }}</flux:link>
                        @if ($row['user']->is(auth()->user()))
                            <span class="text-sm text-zinc-500">（あなた）</span>
                        @endif
                        <div class="text-xs text-zinc-500">{{ $row['workout']->performed_at->format('Y/m/d') }} 達成</div>
                    </div>
                    <span class="text-xl font-bold">{{ $row['workout']->weight }}kg</span>
                </div>
            @endforeach
        </div>
    @endif
</section>
