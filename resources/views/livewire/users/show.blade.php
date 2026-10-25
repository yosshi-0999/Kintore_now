<?php

use App\Models\Muscle;
use App\Models\User;
use App\Services\MuscleRestService;
use App\Services\PersonalBestService;
use App\Services\WeightProgressService;
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
        $canView = Gate::allows('viewRecords', $this->user);

        if (! $canView) {
            return ['canView' => false, 'isSelf' => false];
        }

        return [
            'canView' => true,
            'isSelf' => Auth::user()->is($this->user),
            'personalBests' => app(PersonalBestService::class)->forUser($this->user),
            'weightProgress' => app(WeightProgressService::class)->forUser($this->user),
            ...app(MuscleRestService::class)->overview($this->user),
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
        {{--
            今の状態を見る欄はタブにまとめて、1つずつ表示する（縦に長くならないように）。
            切り替えは Alpine で行い、サーバーには問い合わせない。最初は自己ベストだけを表示し、
            ほかのパネルは JS が読み込まれる前に見えないよう display:none で出力する。
            タブとパネルには wire:ignore.self を付け、Livewire の再描画で選択中のタブが戻らないようにする（中身は通常どおり更新される）
        --}}
        @php
            $tabs = [
                'best' => '自己ベスト',
                'progress' => '最高重量の推移',
                'resting' => '休ませる筋肉',
                'available' => '使える筋肉',
                'suggestions' => 'おすすめの種目',
            ];
        @endphp

        <div x-data="{ tab: 'best' }" class="rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div role="tablist" aria-label="ユーザーの状態" class="flex gap-1 overflow-x-auto border-b border-zinc-200 px-2 dark:border-zinc-700">
                @foreach ($tabs as $key => $label)
                    <button
                        wire:ignore.self
                        type="button"
                        role="tab"
                        id="tab-{{ $key }}"
                        aria-controls="panel-{{ $key }}"
                        aria-selected="{{ $key === 'best' ? 'true' : 'false' }}"
                        :aria-selected="(tab === '{{ $key }}').toString()"
                        x-on:click="tab = '{{ $key }}'"
                        @class([
                            '-mb-px flex shrink-0 items-center gap-1 border-b-2 px-3 py-3 text-sm font-medium whitespace-nowrap',
                            'border-amber-500 text-amber-700 dark:text-amber-300' => $key === 'best',
                            'border-transparent text-zinc-500' => $key !== 'best',
                        ])
                        :class="tab === '{{ $key }}'
                            ? 'border-amber-500 text-amber-700 dark:text-amber-300'
                            : 'border-transparent text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200'"
                    >
                        @if ($key === 'best')
                            <flux:icon.trophy class="size-4 text-amber-500" />
                        @endif
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div wire:ignore.self role="tabpanel" id="panel-best" aria-labelledby="tab-best" x-show="tab === 'best'" class="p-4">
                @if ($personalBests->isEmpty())
                    <flux:text>
                        {{ $isSelf ? '「種目から入力」で種目を選んで記録すると、ここに種目ごとの自己ベストが表示されます。' : 'まだ自己ベストはありません。' }}
                    </flux:text>
                @else
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
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

            <div wire:ignore.self role="tabpanel" id="panel-progress" aria-labelledby="tab-progress" x-show="tab === 'progress'" style="display: none" class="p-4">
                @if ($weightProgress->isEmpty())
                    <flux:text>
                        {{ $isSelf ? '「種目から入力」で種目を選び、重量を入れて記録すると、ここに種目ごとの推移が表示されます。' : 'まだ表示できる記録がありません。' }}
                    </flux:text>
                @else
                    {{-- データが変わったら作り直す（wire:key が変わると要素ごと置き換わる）。それ以外の再描画ではグラフに触らない --}}
                    <div
                        wire:ignore
                        wire:key="weight-chart-{{ md5($weightProgress->toJson()) }}"
                        x-data="weightChart(@js($weightProgress))"
                        class="space-y-3"
                    >
                        <flux:select x-model.number="exerciseId" label="種目" class="max-w-xs">
                            @foreach ($weightProgress as $series)
                                <flux:select.option value="{{ $series['exercise_id'] }}">{{ $series['name'] }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <div class="h-64">
                            <canvas x-ref="canvas" aria-label="最高重量の推移グラフ" role="img"></canvas>
                        </div>
                        <flux:text size="sm">日ごとの最高重量です（自重の記録は含みません）。</flux:text>
                    </div>
                @endif
            </div>

            <div wire:ignore.self role="tabpanel" id="panel-resting" aria-labelledby="tab-resting" x-show="tab === 'resting'" style="display: none" class="p-4">
                @forelse ($resting as $item)
                    <div class="mt-2 flex justify-between text-sm first:mt-0" wire:key="resting-{{ $item['muscle']->id }}">
                        <span>{{ $item['muscle']->name }}</span>
                        <span class="text-zinc-500">{{ $item['recovers_at']->format('m/d H:i') }} に回復</span>
                    </div>
                @empty
                    <flux:text>休息中の筋肉はありません。</flux:text>
                @endforelse
            </div>

            <div wire:ignore.self role="tabpanel" id="panel-available" aria-labelledby="tab-available" x-show="tab === 'available'" style="display: none" class="p-4">
                <div class="flex flex-wrap gap-2">
                    @foreach ($available as $muscle)
                        <span wire:key="available-{{ $muscle->id }}" class="rounded-full bg-zinc-100 px-3 py-1 text-sm dark:bg-zinc-700">{{ $muscle->name }}</span>
                    @endforeach
                </div>
            </div>

            <div wire:ignore.self role="tabpanel" id="panel-suggestions" aria-labelledby="tab-suggestions" x-show="tab === 'suggestions'" style="display: none" class="p-4">
                <div class="grid gap-1 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($suggestions as $exercise)
                        <div wire:key="suggestion-{{ $exercise->id }}">
                            {{ $exercise->name }}<span class="text-zinc-500">（{{ $exercise->muscle->name }}）</span>
                        </div>
                    @empty
                        <flux:text>今はすべての筋肉が休息中です。しっかり休みましょう。</flux:text>
                    @endforelse
                </div>
            </div>
        </div>

        @if ($isSelf)
            <x-collapsible-section title="筋トレ内容を記録">
                <livewire:workouts.form />
            </x-collapsible-section>
        @endif

        {{-- 検索条件や2ページ目以降の URL で開いたときは、結果が見えるように開いておく --}}
        <x-collapsible-section title="記録" :open="$isSearching || $workouts->currentPage() > 1">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
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
        </x-collapsible-section>
    @endunless
</section>
