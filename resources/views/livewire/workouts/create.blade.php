<?php

use App\Models\WorkoutPlan;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new class extends Component {
    #[Url]
    public ?int $plan = null;

    public function mount(): void
    {
        // 他人のプランIDが指定されたら拒否する
        if ($this->plan !== null) {
            $this->authorize('view', WorkoutPlan::findOrFail($this->plan));
        }
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-6">
    <div>
        <flux:heading size="xl" level="1">筋トレ内容を記録</flux:heading>
        @if ($plan)
            <flux:text class="mt-1">通知プランの内容を入力済みです。必要に応じて変更してください。</flux:text>
        @endif
    </div>

    <livewire:workouts.form :plan-id="$plan" />

    <flux:link :href="route('mypage')" wire:navigate>マイページへ戻る</flux:link>
</section>
