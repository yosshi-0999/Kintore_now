<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public function with(): array
    {
        // 自分に閲覧許可を出してくれたユーザーのみ（view_permissions の viewer が自分の行）
        return [
            'users' => Auth::user()->viewableOwners()->orderBy('name')->paginate(30, ['users.id', 'users.name']),
        ];
    }
}; ?>

<section class="w-full space-y-6">
    <div>
        <flux:heading size="xl" level="1">ユーザー一覧</flux:heading>
        <flux:text class="mt-1">あなたに記録の閲覧を許可しているユーザーです。</flux:text>
    </div>

    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @forelse ($users as $user)
            <div class="py-2" wire:key="user-{{ $user->id }}">
                <flux:link :href="route('users.show', $user)" wire:navigate>{{ $user->name }}</flux:link>
            </div>
        @empty
            <flux:text>まだ閲覧を許可してくれたユーザーはいません。</flux:text>
        @endforelse
    </div>

    {{ $users->links() }}
</section>
