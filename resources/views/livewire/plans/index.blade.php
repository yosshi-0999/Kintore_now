<?php

use App\Models\WorkoutPlan;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public function toggle(int $id): void
    {
        $plan = WorkoutPlan::findOrFail($id);
        $this->authorize('update', $plan);

        $plan->update(['is_enabled' => ! $plan->is_enabled]);
    }

    public function delete(int $id): void
    {
        $plan = WorkoutPlan::findOrFail($id);
        $this->authorize('delete', $plan);

        $plan->delete();
    }

    public function with(): array
    {
        return [
            'plans' => Auth::user()->workoutPlans()->with('muscles')->orderByDesc('scheduled_at')->get(),
        ];
    }
}; ?>

<section class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" level="1">通知プラン</flux:heading>
        <flux:button :href="route('plans.create')" variant="primary" wire:navigate>新しいプラン</flux:button>
    </div>

    <flux:text>予定日時になると、内容をメールでお知らせします（1回のみ）。</flux:text>

    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @forelse ($plans as $plan)
            <div class="flex flex-wrap items-center justify-between gap-4 py-3" wire:key="plan-{{ $plan->id }}">
                <div class="text-sm">
                    <div class="font-semibold">{{ $plan->scheduled_at->format('Y/m/d H:i') }} ／ {{ $plan->menu_name }}</div>
                    <div class="text-zinc-500">
                        場所：{{ $plan->place }} ／ 筋肉：{{ $plan->muscles->pluck('name')->join('、') }}
                    </div>
                    <div class="text-zinc-500">
                        @if ($plan->sent_at)
                            送信済み（{{ $plan->sent_at->format('m/d H:i') }}）
                        @elseif ($plan->is_enabled)
                            通知予定
                        @else
                            通知オフ
                        @endif
                    </div>
                </div>
                <div class="flex gap-2">
                    <flux:button size="sm" wire:click="toggle({{ $plan->id }})">
                        {{ $plan->is_enabled ? '通知をオフ' : '通知をオン' }}
                    </flux:button>
                    <flux:button size="sm" :href="route('plans.edit', $plan)" wire:navigate>編集</flux:button>
                    <flux:button size="sm" variant="danger" wire:click="delete({{ $plan->id }})" wire:confirm="このプランを削除しますか？">削除</flux:button>
                </div>
            </div>
        @empty
            <flux:text>まだプランがありません。</flux:text>
        @endforelse
    </div>
</section>
