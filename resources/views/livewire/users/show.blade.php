<?php

use App\Models\User;
use App\Services\MuscleRestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component {
    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    /**
     * フォームで保存されたら表示を更新する
     */
    #[On('workout-saved')]
    public function refreshRecords(): void
    {
    }

    public function with(): array
    {
        $rest = app(MuscleRestService::class);
        $canView = Gate::allows('viewRecords', $this->user);

        if (! $canView) {
            return ['canView' => false, 'isSelf' => false];
        }

        return [
            'canView' => true,
            'isSelf' => Auth::user()->is($this->user),
            'resting' => $rest->restingMuscles($this->user),
            'available' => $rest->availableMuscles($this->user),
            'suggestions' => $rest->suggestedExercises($this->user),
            'workouts' => $this->user->workouts()->with('muscles')->latest('performed_at')->limit(20)->get(),
        ];
    }
}; ?>

<section class="w-full space-y-8">
    <flux:heading size="xl" level="1">{{ $user->name }} さんのページ</flux:heading>

    @unless ($canView)
        <flux:text>このユーザーの記録は、許可されたユーザーのみ閲覧できます。</flux:text>
    @else
        <div class="grid gap-6 md:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="lg">休ませる筋肉</flux:heading>
                @forelse ($resting as $item)
                    <div class="mt-2 flex justify-between text-sm" wire:key="resting-{{ $item['muscle']->id }}">
                        <span>{{ $item['muscle']->name }}</span>
                        <span class="text-zinc-500">{{ $item['recovers_at']->format('m/d H:i') }} に回復</span>
                    </div>
                @empty
                    <flux:text class="mt-2">休息中の筋肉はありません。</flux:text>
                @endforelse
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="lg">使える筋肉</flux:heading>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach ($available as $muscle)
                        <span wire:key="available-{{ $muscle->id }}" class="rounded-full bg-zinc-100 px-3 py-1 text-sm dark:bg-zinc-700">{{ $muscle->name }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="lg">おすすめの種目</flux:heading>
            <div class="mt-2 grid gap-1 text-sm sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($suggestions as $exercise)
                    <div wire:key="suggestion-{{ $exercise->id }}">
                        {{ $exercise->name }}<span class="text-zinc-500">（{{ $exercise->muscle->name }}）</span>
                    </div>
                @empty
                    <flux:text>今はすべての筋肉が休息中です。しっかり休みましょう。</flux:text>
                @endforelse
            </div>
        </div>

        @if ($isSelf)
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="lg" class="mb-4">筋トレ内容を記録</flux:heading>
                <livewire:workouts.form />
            </div>
        @endif

        <div>
            <flux:heading size="lg">最近の記録</flux:heading>
            <div class="mt-2 divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($workouts as $workout)
                    <div class="py-2 text-sm" wire:key="workout-{{ $workout->id }}">
                        <div class="flex justify-between">
                            <span class="font-semibold">{{ $workout->menu_name }}</span>
                            <span class="text-zinc-500">{{ $workout->performed_at->format('Y/m/d H:i') }}</span>
                        </div>
                        @if ($workout->reps !== null)
                            <div>
                                {{ $workout->weight !== null ? $workout->weight.'kg × ' : '' }}{{ $workout->reps }}回 × {{ $workout->sets }}セット
                            </div>
                        @endif
                        <div class="text-zinc-500">
                            {{ $workout->muscles->pluck('name')->join('、') }} ／ 休憩 {{ $workout->rest_days }} 日
                        </div>
                    </div>
                @empty
                    <flux:text>まだ記録がありません。</flux:text>
                @endforelse
            </div>
        </div>
    @endunless
</section>
