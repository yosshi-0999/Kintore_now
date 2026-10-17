<?php

use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function with(): array
    {
        // アカウント名は常に公開
        return [
            'users' => User::query()->orderBy('name')->paginate(30, ['id', 'name']),
        ];
    }
}; ?>

<section class="w-full space-y-6">
    <flux:heading size="xl" level="1">ユーザー一覧</flux:heading>

    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @foreach ($users as $user)
            <div class="py-2" wire:key="user-{{ $user->id }}">
                <flux:link :href="route('users.show', $user)" wire:navigate>{{ $user->name }}</flux:link>
            </div>
        @endforeach
    </div>

    {{ $users->links() }}
</section>
