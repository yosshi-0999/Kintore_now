<?php

use App\Services\BlogPostService;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component {
    #[Locked]
    public string $slug;

    public function mount(string $slug): void
    {
        abort_if(app(BlogPostService::class)->find($slug) === null, 404);

        $this->slug = $slug;
    }

    public function with(): array
    {
        return [
            'post' => app(BlogPostService::class)->find($this->slug),
        ];
    }
}; ?>

<article class="mx-auto w-full max-w-3xl space-y-6">
    <div>
        <flux:text size="sm">{{ $post->publishedAt->format('Y/m/d') }}</flux:text>
        <flux:heading size="xl" level="1" class="mt-1">{{ $post->title }}</flux:heading>
    </div>

    {{-- 本文は Markdown から変換した HTML（記事内の HTML タグは BlogPostService で除去済み） --}}
    <div class="space-y-4 leading-relaxed text-zinc-700 dark:text-zinc-300 [&_a]:underline [&_blockquote]:border-l-4 [&_blockquote]:border-zinc-300 [&_blockquote]:pl-4 [&_blockquote]:text-zinc-500 dark:[&_blockquote]:border-zinc-600 dark:[&_blockquote]:text-zinc-400 [&_h2]:mt-8 [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-zinc-900 dark:[&_h2]:text-white [&_h3]:mt-6 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-zinc-900 dark:[&_h3]:text-white [&_li]:mt-1 [&_ol]:list-decimal [&_ol]:pl-6 [&_strong]:text-zinc-900 dark:[&_strong]:text-white [&_table]:block [&_table]:w-full [&_table]:overflow-x-auto [&_table]:text-sm [&_td]:border [&_td]:border-zinc-200 [&_td]:px-3 [&_td]:py-2 dark:[&_td]:border-zinc-700 [&_th]:border [&_th]:border-zinc-200 [&_th]:bg-zinc-50 [&_th]:px-3 [&_th]:py-2 [&_th]:text-left dark:[&_th]:border-zinc-700 dark:[&_th]:bg-zinc-800 [&_ul]:list-disc [&_ul]:pl-6">
        {!! $post->html !!}
    </div>

    <flux:link :href="route('blog.index')" wire:navigate>記事一覧へ戻る</flux:link>
</article>
