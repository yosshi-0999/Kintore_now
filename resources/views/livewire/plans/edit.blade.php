<?php

use App\Actions\SaveWorkoutPlan;
use App\Models\Muscle;
use App\Models\WorkoutPlan;
use App\Services\MuscleRestService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    public ?int $planId = null;

    public string $scheduled_at = '';
    public string $place = '';
    public string $menu_name = '';
    public array $muscle_ids = [];

    public function mount(?WorkoutPlan $plan = null): void
    {
        if ($plan?->exists) {
            $this->authorize('update', $plan);

            $this->planId = $plan->id;
            $this->scheduled_at = $plan->scheduled_at->format('Y-m-d\TH:i');
            $this->place = $plan->place;
            $this->menu_name = $plan->menu_name;
            $this->muscle_ids = $plan->muscles->pluck('id')->map(fn ($id) => (string) $id)->all();

            return;
        }

        $this->scheduled_at = now()->addDay()->setTime(18, 0)->format('Y-m-d\TH:i');
    }

    protected function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'place' => ['required', 'string', 'max:100'],
            'menu_name' => ['required', 'string', 'max:100'],
            'muscle_ids' => ['required', 'array', 'min:1'],
            'muscle_ids.*' => ['integer', 'exists:muscles,id'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attributeを入力してください。',
            'muscle_ids.required' => '筋肉を1つ以上選んでください。',
            'max' => ':attributeは:max文字以内で入力してください。',
            'date' => ':attributeの形式が正しくありません。',
            'scheduled_at.after' => '日時は現在より後を指定してください。',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'scheduled_at' => '日時',
            'place' => '場所',
            'menu_name' => 'メニュー',
            'muscle_ids' => '筋肉',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $plan = $this->planId ? WorkoutPlan::findOrFail($this->planId) : null;

        if ($plan) {
            $this->authorize('update', $plan);
        }

        // 予定日時の時点で休息中の筋肉が含まれていたら作成・更新できない
        $resting = app(MuscleRestService::class)
            ->restingAmong(Auth::user(), $this->muscle_ids, Carbon::parse($this->scheduled_at));

        if ($resting->isNotEmpty()) {
            $names = $resting->map(fn (array $item) => $item['muscle']->name.'（'.$item['recovers_at']->format('m/d H:i').' に回復）')->join('、');
            $this->addError('muscle_ids', "予定日時に休息中の筋肉が含まれています：{$names}");

            return;
        }

        app(SaveWorkoutPlan::class)(Auth::user(), $validated, $plan);

        $this->redirectRoute('plans.index', navigate: true);
    }

    public function with(): array
    {
        return [
            'muscles' => Muscle::orderBy('id')->get(),
        ];
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-6">
    <flux:heading size="xl" level="1">{{ $planId ? '通知プランを編集' : '通知プランを作成' }}</flux:heading>

    <form wire:submit="save" class="space-y-6">
        <flux:input wire:model="scheduled_at" type="datetime-local" label="日時" required />

        <flux:input wire:model="place" label="場所" placeholder="例：〇〇ジム" required />

        <flux:input wire:model="menu_name" label="メニュー" placeholder="例：胸の日" required />

        <flux:checkbox.group wire:model="muscle_ids" label="使う筋肉">
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach ($muscles as $muscle)
                    <flux:checkbox value="{{ $muscle->id }}" label="{{ $muscle->name }}" />
                @endforeach
            </div>
        </flux:checkbox.group>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary">保存する</flux:button>
            <flux:button :href="route('plans.index')" wire:navigate>キャンセル</flux:button>
        </div>
    </form>
</section>
