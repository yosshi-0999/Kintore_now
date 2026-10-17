<?php

use App\Actions\RecordWorkout;
use App\Models\Exercise;
use App\Models\Muscle;
use App\Models\WorkoutPlan;
use App\Services\MuscleRestService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    public string $performed_at = '';
    public string $menu_name = '';
    public string $weight = '';
    public string $reps = '';
    public string $sets = '';
    public array $muscle_ids = [];
    public int $rest_days = 2;
    public string $exercise_id = '';

    /**
     * 確認画面に表示中の休息中の筋肉（空なら確認画面は出ていない）
     *
     * @var array<int, array{muscle_id: int, name: string, recovers_at: string}>
     */
    #[Locked]
    public array $restWarnings = [];

    /**
     * 「続ける」で確認済みの筋肉ID
     *
     * @var array<int, int>
     */
    #[Locked]
    public array $acknowledgedMuscleIds = [];

    public function mount(?int $planId = null, ?int $muscleId = null): void
    {
        $this->performed_at = now()->format('Y-m-d\TH:i');

        if ($planId !== null) {
            $plan = WorkoutPlan::with('muscles')->findOrFail($planId);
            $this->authorize('view', $plan);

            $this->menu_name = $plan->menu_name;
            $this->muscle_ids = $plan->muscles->pluck('id')->map(fn ($id) => (string) $id)->all();
        } elseif ($muscleId !== null) {
            $this->fillFromMuscle(Muscle::findOrFail($muscleId));
        }
    }

    /**
     * ダッシュボードの筋肉図から来たとき、その筋肉の最初の種目を初期値として入れる
     */
    private function fillFromMuscle(Muscle $muscle): void
    {
        $exercise = $muscle->exercises()->orderBy('id')->first();

        if ($exercise === null) {
            $this->menu_name = $muscle->name;
            $this->muscle_ids = [(string) $muscle->id];

            return;
        }

        $this->exercise_id = (string) $exercise->id;
        $this->updatedExerciseId($this->exercise_id);
    }

    protected function rules(): array
    {
        return [
            'performed_at' => ['required', 'date'],
            'menu_name' => ['required', 'string', 'max:100'],
            'weight' => ['nullable', 'numeric', 'between:0,500', 'multiple_of:0.5'],
            'reps' => ['required', 'integer', 'between:1,100'],
            'sets' => ['required', 'integer', 'between:1,20'],
            'muscle_ids' => ['required', 'array', 'min:1'],
            'muscle_ids.*' => ['integer', 'exists:muscles,id'],
            'rest_days' => ['required', 'integer', 'between:0,'.MuscleRestService::MAX_REST_DAYS],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attributeを入力してください。',
            'muscle_ids.required' => '使用した筋肉を1つ以上選んでください。',
            'max' => ':attributeは:max文字以内で入力してください。',
            'date' => ':attributeの形式が正しくありません。',
            'between' => ':attributeは:min〜:maxの範囲で選んでください。',
            'numeric' => ':attributeは数字で入力してください。',
            'integer' => ':attributeは整数で入力してください。',
            'weight.between' => ':attributeは:min〜:maxの範囲で入力してください。',
            'weight.multiple_of' => ':attributeは:value kg 刻みで入力してください。',
            'reps.between' => ':attributeは:min〜:maxの範囲で入力してください。',
            'sets.between' => ':attributeは:min〜:maxの範囲で入力してください。',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'performed_at' => '実行日時',
            'menu_name' => 'メニュー',
            'weight' => '重量',
            'reps' => '回数',
            'sets' => 'セット数',
            'muscle_ids' => '使用した筋肉',
            'rest_days' => '休憩日数',
        ];
    }

    /**
     * 種目を選んだら、メニュー名・筋肉・休憩日数を初期値として入れる
     */
    public function updatedExerciseId(string $value): void
    {
        $exercise = Exercise::find($value);

        if ($exercise === null) {
            return;
        }

        $this->menu_name = $exercise->name;
        $this->muscle_ids = [(string) $exercise->muscle_id];
        $this->rest_days = $exercise->default_rest_days;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $resting = app(MuscleRestService::class)->restingAmong(Auth::user(), $this->muscle_ids);
        $unacknowledged = $resting->except($this->acknowledgedMuscleIds);

        // 休息中の筋肉が未確認なら保存せず、確認を表示する
        if ($unacknowledged->isNotEmpty()) {
            $this->restWarnings = $resting->map(fn (array $item) => [
                'muscle_id' => $item['muscle']->id,
                'name' => $item['muscle']->name,
                'recovers_at' => $item['recovers_at']->format('Y/m/d H:i'),
            ])->values()->all();

            return;
        }

        app(RecordWorkout::class)(Auth::user(), $validated);

        $this->reset('menu_name', 'weight', 'reps', 'sets', 'muscle_ids', 'exercise_id', 'restWarnings', 'acknowledgedMuscleIds');
        $this->rest_days = 2;
        $this->performed_at = now()->format('Y-m-d\TH:i');

        $this->dispatch('workout-saved');
        session()->flash('workout-status', '記録を保存しました。');
    }

    /**
     * 確認で「続ける」を選んだ
     */
    public function continueSave(): void
    {
        // 確認を表示していないのに呼ばれた場合は何もしない
        if ($this->restWarnings === []) {
            return;
        }

        $this->acknowledgedMuscleIds = array_column($this->restWarnings, 'muscle_id');
        $this->restWarnings = [];

        $this->save();
    }

    /**
     * 確認で「戻る」を選んだ
     */
    public function cancelSave(): void
    {
        $this->reset('restWarnings', 'acknowledgedMuscleIds');
    }

    public function with(): array
    {
        return [
            'muscles' => Muscle::orderBy('id')->get(),
            'exercises' => Exercise::with('muscle')->orderBy('muscle_id')->orderBy('id')->get(),
            'maxRestDays' => MuscleRestService::MAX_REST_DAYS,
        ];
    }
}; ?>

<div>
    @if (session('workout-status'))
        <div class="mb-4 rounded-lg border border-green-300 bg-green-50 p-3 text-sm text-green-800 dark:border-green-700 dark:bg-green-950 dark:text-green-200">
            {{ session('workout-status') }}
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        <flux:select wire:model.live="exercise_id" label="種目から入力（任意）">
            <flux:select.option value="">選択しない</flux:select.option>
            @foreach ($exercises as $exercise)
                <flux:select.option value="{{ $exercise->id }}">{{ $exercise->name }}（{{ $exercise->muscle->name }}）</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model="performed_at" type="datetime-local" label="実行日時" required />

        <flux:input wire:model="menu_name" label="メニュー" placeholder="例：ベンチプレス" required />

        <div class="grid grid-cols-3 gap-4">
            <flux:input wire:model="weight" type="number" step="0.5" min="0" max="500" label="重量（kg）" placeholder="自重は空欄" />
            <flux:input wire:model="reps" type="number" step="1" min="1" max="100" label="回数" required />
            <flux:input wire:model="sets" type="number" step="1" min="1" max="20" label="セット数" required />
        </div>

        <flux:checkbox.group wire:model="muscle_ids" label="使用した筋肉">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach ($muscles as $muscle)
                    <flux:checkbox value="{{ $muscle->id }}" label="{{ $muscle->name }}" />
                @endforeach
            </div>
        </flux:checkbox.group>

        <flux:select wire:model="rest_days" label="休憩日数">
            @for ($i = 0; $i <= $maxRestDays; $i++)
                <flux:select.option value="{{ $i }}">{{ $i }} 日</flux:select.option>
            @endfor
        </flux:select>

        <flux:button type="submit" variant="primary">記録する</flux:button>
    </form>

    @if ($restWarnings !== [])
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
            <div class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800">
                <flux:heading size="lg">休息中の筋肉が含まれています</flux:heading>

                <flux:text>次の筋肉はまだ回復していません。このまま記録しますか？</flux:text>

                <ul class="space-y-1 text-sm">
                    @foreach ($restWarnings as $warning)
                        <li wire:key="warning-{{ $warning['muscle_id'] }}">
                            <span class="font-semibold">{{ $warning['name'] }}</span>
                            （回復予定：{{ $warning['recovers_at'] }}）
                        </li>
                    @endforeach
                </ul>

                <div class="flex justify-end gap-2">
                    <flux:button wire:click="cancelSave">戻る</flux:button>
                    <flux:button wire:click="continueSave" variant="danger">続ける</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
