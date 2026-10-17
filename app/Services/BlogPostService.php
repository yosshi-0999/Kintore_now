<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * ブログ記事の読み込み。
 *
 * 記事は DB ではなく resources/posts/ の Markdown ファイルで管理する（1記事1ファイル）。
 * ファイル名（拡張子なし）が URL のスラッグになる。先頭に次の形式のヘッダーを書く。
 *
 *   ---
 *   title: 記事のタイトル
 *   date: 2026-10-17
 *   description: 一覧に表示する説明
 *   ---
 */
class BlogPostService
{
    /**
     * スラッグに使える文字（ルートの制約と合わせる）
     * 小文字英数字をハイフンでつないだものが記事名として使える
     */
    public const SLUG_PATTERN = '[a-z0-9]+(?:-[a-z0-9]+)*';

    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? resource_path('posts');
    }

    /**
     * 公開日の新しい順の記事一覧
     *
     * @return Collection<int, BlogPost>
     */
    public function all(): Collection
    {
        $files = glob($this->directory.'/*.md') ?: [];

        return collect($files)
            ->map(fn (string $path) => $this->parse($path))
            ->filter()
            ->sortBy([['publishedAt', 'desc'], ['slug', 'asc']])
            ->values();
    }

    public function find(string $slug): ?BlogPost
    {
        // ファイルパスを組み立てず一覧から探すので、../ などで他のファイルは読めない
        return $this->all()->firstWhere('slug', $slug);
    }

    /**
     * 正規表現には u を付ける（付けないと \R が「者」などに含まれる 0x85 を改行とみなす）
     */
    private function parse(string $path): ?BlogPost
    {
        $slug = basename($path, '.md');

        if (! preg_match('/\A'.self::SLUG_PATTERN.'\z/u', $slug)) {
            return null;
        }

        $content = file_get_contents($path);

        if ($content === false || ! preg_match('/\A---\R(.*?)\R---\R(.*)\z/su', $content, $matches)) {
            return null;
        }

        $meta = $this->parseHeader($matches[1]);

        if (! isset($meta['title'], $meta['date'])) {
            return null;
        }

        return new BlogPost(
            slug: $slug,
            title: $meta['title'],
            publishedAt: CarbonImmutable::parse($meta['date']),
            description: $meta['description'] ?? '',
            // 記事内の HTML は使わせない（Markdown のみ）
            html: Str::markdown($matches[2], [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]),
        );
    }

    /**
     * 「key: value」形式のヘッダーを読む
     *
     * @return array<string, string>
     */
    private function parseHeader(string $header): array
    {
        $meta = [];

        foreach (preg_split('/\R/u', $header) as $line) {
            if (preg_match('/\A([a-z_]+):\s*(.*)\z/u', trim($line), $m)) {
                $meta[$m[1]] = trim($m[2]);
            }
        }

        return $meta;
    }
}
