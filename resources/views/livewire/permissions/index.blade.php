<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $email = '';

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email', 'not_in:'.Auth::user()->email],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'email.exists' => 'このメールアドレスのユーザーは見つかりません。',
            'email.not_in' => '自分自身は指定できません。',
        ];
    }

    public function grant(): void
    {
        $this->validate();

        $viewer = User::where('email', $this->email)->firstOrFail();
        Auth::user()->viewers()->syncWithoutDetaching([$viewer->id]);

        $this->reset('email');
    }

    public function revoke(int $viewerId): void
    {
        Auth::user()->viewers()->detach($viewerId);
    }

    public function with(): array
    {
        return [
            'viewers' => Auth::user()->viewers()->orderBy('name')->get(['users.id', 'users.name']),
            'owners' => Auth::user()->viewableOwners()->orderBy('name')->get(['users.id', 'users.name']),
        ];
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-8">
    <flux:heading size="xl" level="1">閲覧許可</flux:heading>

    <div class="space-y-4">
        <flux:heading size="lg">自分の記録を見せる相手</flux:heading>

        <form wire:submit="grant" class="flex items-end gap-2">
            <div class="flex-1">
                <flux:input wire:model="email" type="email" label="相手のメールアドレス" />
            </div>
            <flux:button type="submit" variant="primary">許可する</flux:button>
        </form>

        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($viewers as $viewer)
                <div class="flex items-center justify-between py-2" wire:key="viewer-{{ $viewer->id }}">
                    <span>{{ $viewer->name }}</span>
                    <flux:button size="sm" wire:click="revoke({{ $viewer->id }})">許可を取り消す</flux:button>
                </div>
            @empty
                <flux:text>まだ誰にも許可していません。</flux:text>
            @endforelse
        </div>
    </div>

    <div class="space-y-4">
        <flux:heading size="lg">記録を見せてくれている人</flux:heading>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($owners as $owner)
                <div class="py-2" wire:key="owner-{{ $owner->id }}">
                    <flux:link :href="route('users.show', $owner)" wire:navigate>{{ $owner->name }}</flux:link>
                </div>
            @empty
                <flux:text>まだいません。</flux:text>
            @endforelse
        </div>
    </div>
</section>
