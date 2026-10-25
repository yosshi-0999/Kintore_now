@php
    $imageWidth = 704;
    $imageHeight = 480;
    $labels = [
        '胸鎖乳突筋' => [38, 32, 142, 54],
        '大胸筋' => [48, 72, 112, 94],
        '上腕二頭筋' => [22, 118, 108, 140],
        '前鋸筋' => [44, 146, 108, 168],
        '外腹斜筋' => [22, 176, 96, 198],
        '腹直筋' => [18, 230, 80, 252],
        '内転筋群' => [30, 280, 104, 302],
        '大腿四頭筋' => [34, 312, 118, 334],
        '前脛骨筋' => [50, 396, 120, 418],
        '僧帽筋' => [312, 32, 374, 54],
        '三角筋' => [312, 84, 374, 106],
        '広背筋' => [312, 126, 374, 148],
        '前腕伸筋群' => [300, 168, 390, 190],
        '前腕屈筋群' => [300, 280, 390, 302],
        '下腿三頭筋' => [280, 366, 420, 406],
        '棘下筋' => [598, 84, 656, 106],
        '上腕三頭筋' => [596, 118, 680, 140],
        '脊柱起立筋' => [598, 176, 684, 198],
        '大臀筋' => [576, 276, 632, 298],
        'ハムストリングス' => [556, 320, 688, 342],
    ];
@endphp

<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <div>
            <flux:heading size="xl" level="1">今日はどこを鍛える？</flux:heading>
            <flux:text class="mt-1">筋肉の名前をクリックすると、その筋肉の種目を入力した記録フォームが開きます。</flux:text>
        </div>

        <div class="relative mx-auto w-full max-w-3xl overflow-hidden rounded-xl border border-neutral-200 bg-white dark:border-neutral-700">
            <img src="{{ asset('images/muscle-map.jpg') }}" alt="全身の筋肉図" class="block h-auto w-full" width="{{ $imageWidth }}" height="{{ $imageHeight }}">

            @foreach ($labels as $name => [$left, $top, $right, $bottom])
                @isset($muscleIds[$name])
                    <a
                        href="{{ route('workouts.create', ['muscle' => $muscleIds[$name]]) }}"
                        wire:navigate
                        aria-label="{{ $name }}を記録する"
                        title="{{ $name }}を記録する"
                        class="absolute rounded ring-2 ring-transparent transition hover:bg-blue-500/15 hover:ring-blue-500 focus-visible:bg-blue-500/15 focus-visible:ring-blue-500 focus-visible:outline-none"
                        style="left: {{ $left / $imageWidth * 100 }}%; top: {{ $top / $imageHeight * 100 }}%; width: {{ ($right - $left) / $imageWidth * 100 }}%; height: {{ ($bottom - $top) / $imageHeight * 100 }}%;"
                    ></a>
                @endisset
            @endforeach
        </div>
    </div>
</x-layouts.app>
