<?php

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * ブログ記事1本（resources/posts/*.md から読み込む）
 */
final readonly class BlogPost
{
    public function __construct(
        public string $slug,
        public string $title,
        public CarbonImmutable $publishedAt,
        public string $description,
        public string $html,
    ) {}
}
