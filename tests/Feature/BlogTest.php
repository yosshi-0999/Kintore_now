<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BlogPostService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BlogTest extends TestCase
{
    use RefreshDatabase;

    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = storage_path('framework/testing/posts');
        File::ensureDirectoryExists($this->directory);
        File::cleanDirectory($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);

        parent::tearDown();
    }

    private function writePost(string $name, string $content): void
    {
        File::put($this->directory.'/'.$name, $content);
    }

    private function useTestPosts(): void
    {
        $this->app->instance(BlogPostService::class, new BlogPostService($this->directory));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('blog.index'))->assertRedirect(route('login'));
    }

    public function test_index_lists_posts_newest_first_and_links_to_each_post(): void
    {
        $this->writePost('old-post.md', "---\ntitle: 古い記事\ndate: 2026-10-01\ndescription: 古い説明\n---\n本文");
        $this->writePost('new-post.md', "---\ntitle: 新しい記事\ndate: 2026-10-10\n---\n本文");
        $this->useTestPosts();

        $this->actingAs(User::factory()->create())
            ->get(route('blog.index'))
            ->assertOk()
            ->assertSeeInOrder(['新しい記事', '古い記事', '古い説明'])
            ->assertSee(route('blog.show', 'old-post'), false)
            ->assertSee(route('blog.show', 'new-post'), false);
    }

    public function test_show_renders_markdown_and_strips_html(): void
    {
        $this->writePost('sample.md', "---\ntitle: サンプル\ndate: 2026-10-17\n---\n## 見出し\n\n**太字**\n\n<script>alert(1)</script>");
        $this->useTestPosts();

        $this->actingAs(User::factory()->create())
            ->get(route('blog.show', 'sample'))
            ->assertOk()
            ->assertSee('サンプル')
            ->assertSee('<h2>見出し</h2>', false)
            ->assertSee('<strong>太字</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_header_with_multibyte_text_is_read_correctly(): void
    {
        // 「者」は UTF-8 で 0x85 を含む。正規表現が改行と誤認しないこと
        $this->writePost('beginner.md', "---\ntitle: 初心者向けの記事\ndate: 2026-10-17\ndescription: 初心者が読む説明\n---\n本文");
        $this->useTestPosts();

        $post = app(BlogPostService::class)->find('beginner');

        $this->assertSame('初心者向けの記事', $post->title);
        $this->assertSame('初心者が読む説明', $post->description);
    }

    public function test_unknown_post_returns_not_found(): void
    {
        $this->useTestPosts();

        $this->actingAs(User::factory()->create())
            ->get(route('blog.show', 'missing'))
            ->assertNotFound();
    }

    public function test_files_without_header_are_ignored(): void
    {
        $this->writePost('no-header.md', '# ヘッダーなし');
        $this->useTestPosts();

        $this->assertTrue(app(BlogPostService::class)->all()->isEmpty());
    }

    public function test_bundled_posts_are_readable(): void
    {
        $posts = (new BlogPostService)->all();

        $this->assertCount(3, $posts);
        $this->assertTrue($posts->every(fn ($post) => $post->title !== '' && $post->html !== ''));
        $this->assertTrue($posts->every(fn ($post) => mb_check_encoding($post->title.$post->description.$post->html, 'UTF-8')));
    }

    public function test_sidebar_has_blog_link(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertSee(route('blog.index'), false)
            ->assertSee('ブログ');
    }
}
