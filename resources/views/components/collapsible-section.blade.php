@props([
    'title',
    'open' => false,
])

{{--
    見出しのボタンを押すと中身を開閉する欄。
    wire:ignore.self で、Livewire が再描画しても開閉状態（open 属性）を保つ。中身は通常どおり更新される
--}}
<details
    wire:ignore.self
    @if ($open) open @endif
    {{-- class を渡したときは枠の色・背景をそちらで指定する --}}
    {{ $attributes->class(['group rounded-xl border', 'border-zinc-200 dark:border-zinc-700' => ! $attributes->has('class')]) }}
>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-2 p-4 select-none [&::-webkit-details-marker]:hidden">
        <span class="flex items-center gap-2">
            {{ $icon ?? '' }}
            <flux:heading size="lg">{{ $title }}</flux:heading>
        </span>
        <flux:icon.chevron-down class="size-5 text-zinc-500 transition-transform group-open:rotate-180" />
    </summary>

    <div class="px-4 pb-4">
        {{ $slot }}
    </div>
</details>
