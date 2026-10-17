<?php

use App\Services\BlogPostService;
use Livewire\Volt\Component;

new class extends Component {
    public function with(): array
    {
        return [
            'posts' => app(BlogPostService::class)->all(),
        ];
    }
}; ?>

<section class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl" level="1">ブログ</flux:heading>
        <flux:text class="mt-1">筋トレを続けるための豆知識をまとめています。</flux:text>
    </div>

    @if ($posts->isEmpty())
        <flux:text>まだ記事がありません。</flux:text>
    @else
        <div class="space-y-4">
            @foreach ($posts as $post)
                <a
                    href="{{ route('blog.show', $post->slug) }}"
                    wire:navigate
                    wire:key="post-{{ $post->slug }}"
                    class="block rounded-xl border border-zinc-200 p-5 transition hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
                >
                    <flux:text size="sm">{{ $post->publishedAt->format('Y/m/d') }}</flux:text>
                    <flux:heading size="lg" level="2" class="mt-1">{{ $post->title }}</flux:heading>
                    @if ($post->description !== '')
                        <flux:text class="mt-2">{{ $post->description }}</flux:text>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</section>
