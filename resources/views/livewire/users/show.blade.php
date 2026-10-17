<?php

use App\Models\Muscle;
use App\Models\User;
use App\Services\MuscleRestService;
use App\Services\PersonalBestService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public User $user;

    // 検索条件（URL に残して、戻る・共有でも同じ結果を出す）
    #[Url]
    public string $keyword = '';

    #[Url(as: 'muscle')]
    public string $muscle_id = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

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

    protected function rules(): array
    {
        return [
            'keyword' => ['nullable', 'string', 'max:100'],
            'muscle_id' => ['nullable', 'integer', 'exists:muscles,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ];
    }

    protected function messages(): array
    {
        return [
            'max' => ':attributeは:max文字以内で入力してください。',
            'date' => ':attributeの形式が正しくありません。',
            'after_or_equal' => ':attributeは開始日以降を指定してください。',
            'muscle_id.integer' => '筋肉の選択が正しくありません。',
            'muscle_id.exists' => '筋肉の選択が正しくありません。',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'keyword' => 'メニュー名',
            'muscle_id' => '筋肉',
            'from' => '開始日',
            'to' => '終了日',
        ];
    }

    /**
     * 検索条件を変えたら、エラーを表示して1ページ目に戻す
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['keyword', 'muscle_id', 'from', 'to'], true)) {
            $this->resetPage();
            $this->resetErrorBag();
            $this->validate();
        }
    }

    public function clearSearch(): void
    {
        $this->reset('keyword', 'muscle_id', 'from', 'to');
        $this->resetErrorBag();
        $this->resetPage();
    }

    /**
     * @return array{keyword: string, muscle_id: string, from: string, to: string}
     */
    private function searchInput(): array
    {
        return [
            'keyword' => $this->keyword,
            'muscle_id' => $this->muscle_id,
            'from' => $this->from,
            'to' => $this->to,
        ];
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
            'personalBests' => app(PersonalBestService::class)->forUser($this->user),
            'resting' => $rest->restingMuscles($this->user),
            'available' => $rest->availableMuscles($this->user),
            'suggestions' => $rest->suggestedExercises($this->user),
            'muscles' => Muscle::orderBy('id')->get(),
            'isSearching' => collect($this->searchInput())->filter()->isNotEmpty(),
            // 不正な条件（存在しない筋肉、日付でない文字など）は検索に使わない
            'workouts' => $this->user->workouts()
                ->search(Validator::make($this->searchInput(), $this->rules())->valid())
                ->with('muscles')
                ->latest('performed_at')
                ->latest('id')
                ->paginate(20),
        ];
    }
}; ?>

<section class="w-full space-y-8">
    <flux:heading size="xl" level="1">{{ $user->name }} さんのページ</flux:heading>

    @unless ($canView)
        <flux:text>このユーザーの記録は、許可されたユーザーのみ閲覧できます。</flux:text>
    @else
        <div class="rounded-xl border border-amber-300 bg-gradient-to-br from-amber-50 to-orange-50 p-4 dark:border-amber-700 dark:from-amber-950 dark:to-orange-950">
            <div class="flex items-center gap-2">
                <flux:icon.trophy class="size-6 text-amber-500" />
                <flux:heading size="lg">自己ベスト</flux:heading>
            </div>

            @if ($personalBests->isEmpty())
                <flux:text class="mt-2">
                    {{ $isSelf ? '「種目から入力」で種目を選んで記録すると、ここに種目ごとの自己ベストが表示されます。' : 'まだ自己ベストはありません。' }}
                </flux:text>
            @else
                <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($personalBests as $best)
                        <div class="rounded-lg bg-white/80 p-3 shadow-sm dark:bg-zinc-900/60" wire:key="best-{{ $best->exercise_id }}">
                            <div class="text-sm font-semibold text-amber-700 dark:text-amber-300">{{ $best->exercise->name }}</div>
                            <div class="mt-1 flex items-baseline gap-2">
                                <span class="text-3xl font-bold">{{ $best->weight !== null ? $best->weight.'kg' : '自重' }}</span>
                                @if ($best->reps !== null)
                                    <span class="text-sm text-zinc-600 dark:text-zinc-300">× {{ $best->reps }}回 × {{ $best->sets }}セット</span>
                                @endif
                            </div>
                            <div class="mt-1 text-xs text-zinc-500">{{ $best->performed_at->format('Y/m/d') }} 達成</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

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
            <flux:heading size="lg">記録</flux:heading>

            <div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <flux:input wire:model.live.debounce.400ms="keyword" label="メニュー名" placeholder="例：ベンチ" />

                <flux:select wire:model.live="muscle_id" label="筋肉">
                    <flux:select.option value="">すべて</flux:select.option>
                    @foreach ($muscles as $muscle)
                        <flux:select.option value="{{ $muscle->id }}">{{ $muscle->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model.live="from" type="date" label="開始日" />
                <flux:input wire:model.live="to" type="date" label="終了日" />
            </div>

            @if ($isSearching)
                <div class="mt-2 flex items-center gap-3 text-sm text-zinc-500">
                    <span>{{ $workouts->total() }} 件見つかりました</span>
                    <flux:button size="sm" variant="ghost" wire:click="clearSearch">条件をクリア</flux:button>
                </div>
            @endif

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
                    <flux:text class="py-2">{{ $isSearching ? '条件に合う記録がありません。' : 'まだ記録がありません。' }}</flux:text>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $workouts->links() }}
            </div>
        </div>
    @endunless
</section>
