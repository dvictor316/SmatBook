<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\VerifyTenantSession;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\User;
use Database\Seeders\BlogStarterPostsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ForceLogoutExpiredSession::class, EnforceDeviceSessionLimit::class, VerifyTenantSession::class]);
        Storage::fake('public');
    }

    public function test_super_admin_can_create_publish_update_and_delete_a_post_with_a_cover(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $data = [
            'title' => 'Managing stock',
            'category' => 'Inventory',
            'summary' => 'An introduction to stock.',
            'content' => "First line\nSecond line",
            'status' => 'draft',
            'cover' => UploadedFile::fake()->image('cover.jpg'),
        ];
        $this->actingAs($admin)->post(route('super_admin.blog.store'), $data)->assertRedirect();
        $post = BlogPost::firstOrFail();
        Storage::disk('public')->assertExists($post->cover_path);
        $this->get(route('super_admin.blog.index'))->assertOk()->assertSee('Managing stock');
        $this->get(route('super_admin.blog.index'))->assertSee('Blog Posts &amp; Comments', false);
        foreach (['super_admin.blog.create', 'super_admin.blog.edit'] as $name) {
            $parameters = $name === 'super_admin.blog.edit' ? [$post] : [];
            $this->get(route($name, $parameters))->assertOk()
                ->assertSee('Manage articles')->assertSee('Managing stock')
                ->assertSee(route('super_admin.blog.edit', $post))
                ->assertSee(route('super_admin.blog.destroy', $post))
                ->assertSee('assets/css/blog-admin.css')
                ->assertSee('name="content_format"', false);
        }
        $this->get(route('super_admin.blog.edit', $post))->assertSee('First line');
        $this->getJson(route('blog.show', $post->slug))->assertNotFound();

        unset($data['cover']);
        $data['status'] = 'published';
        $this->put(route('super_admin.blog.update', $post), $data)->assertRedirect();
        $post->refresh();
        $this->assertNotNull($post->published_at);
        $this->get(route('blog.show', $post->slug))->assertOk()->assertSee('Second line');

        $cover = $post->cover_path;
        $slug = $post->slug;
        $publishedAt = $post->published_at->toDateTimeString();
        $data['cover'] = UploadedFile::fake()->image('replacement.png');
        $this->put(route('super_admin.blog.update', $post), $data)->assertRedirect();
        $post->refresh();
        $this->assertSame($slug, $post->slug);
        $this->assertSame($publishedAt, $post->published_at->toDateTimeString());
        Storage::disk('public')->assertMissing($cover);
        Storage::disk('public')->assertExists($post->cover_path);
        unset($data['cover']);
        $data['status'] = 'draft';
        $data['remove_cover'] = 1;
        $cover = $post->cover_path;
        $this->put(route('super_admin.blog.update', $post), $data)->assertRedirect();
        $post->refresh();
        $this->assertNull($post->published_at);
        $this->assertNull($post->cover_path);
        Storage::disk('public')->assertMissing($cover);
        $this->getJson(route('blog.show', $slug))->assertNotFound();
        unset($data['remove_cover']);
        $data['status'] = 'published';
        $this->put(route('super_admin.blog.update', $post), $data)->assertRedirect();
        $post->refresh();
        $this->assertNotNull($post->published_at);
        $this->get(route('blog.show', $slug))->assertOk();

        $data['cover'] = UploadedFile::fake()->image('final.jpg');
        $this->put(route('super_admin.blog.update', $post), $data)->assertRedirect();
        $post->refresh();
        $cover = $post->cover_path;
        $post->comments()->create(['name' => 'Reader', 'body' => 'Comment']);
        $this->delete(route('super_admin.blog.destroy', $post))->assertRedirect(route('super_admin.blog.index'));
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('blog_comments', ['blog_post_id' => $post->id]);
        Storage::disk('public')->assertMissing($cover);
        $this->getJson(route('blog.show', $post->slug))->assertNotFound();
    }

    public function test_blog_pagination_preserves_filters_and_supports_next_pages(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $post = BlogPost::create([
                'title' => "Needle post $i", 'slug' => "needle-post-$i", 'category' => 'Inventory',
                'summary' => 'Summary', 'content' => 'Content', 'status' => 'published', 'published_at' => now(),
            ]);
        }
        for ($i = 1; $i <= 21; $i++) {
            $post->comments()->create(['name' => "Reader $i", 'body' => 'Comment']);
        }
        $post->comments()->update(['approved' => true]);

        $this->get(route('blog.index', ['search' => 'needle', 'category' => 'Inventory']))
            ->assertOk()->assertSee('search=needle&amp;category=Inventory&amp;page=2', false)
            ->assertDontSee('Rows per page');
        $this->get(route('blog.index', ['search' => 'needle', 'category' => 'Inventory', 'page' => 3]))
            ->assertOk()->assertSee('Needle post 1');
        $this->get(route('blog.show', $post->slug))->assertOk()->assertSee('page=2')->assertDontSee('Rows per page');
        $this->get(route('blog.show', ['slug' => $post->slug, 'page' => 2]))->assertOk()->assertSee('Reader 21');

        $this->actingAs(User::factory()->create(['role' => 'super_admin']));
        $this->get(route('super_admin.blog.index'))->assertOk()->assertSee('page=2')->assertDontSee('Rows per page');
        $this->get(route('super_admin.blog.create'))->assertOk()
            ->assertSee(route('super_admin.blog.index', ['page' => 2]), false);
        $this->get(route('super_admin.blog.comments.index'))->assertOk()->assertSee('page=2')->assertDontSee('Rows per page');
    }

    public function test_public_search_and_comments_only_show_published_content(): void
    {
        $published = BlogPost::create([
            'title' => 'Inventory tips', 'slug' => 'inventory-tips', 'category' => 'Inventory',
            'summary' => 'Stock summary', 'content' => 'Count your stock.', 'status' => 'published', 'published_at' => now(),
        ]);
        $draft = BlogPost::create([
            'title' => 'Private draft', 'slug' => 'private-draft', 'category' => 'Draft category',
            'summary' => 'Hidden', 'content' => 'Hidden content', 'status' => 'draft',
        ]);

        $this->get(route('blog.index', ['search' => 'stock', 'category' => 'Inventory']))
            ->assertOk()->assertSee('Inventory tips')->assertDontSee('Private draft')->assertDontSee('Draft category');
        $this->get(route('blog.index', ['search' => 'missing']))->assertOk()->assertDontSee('Inventory tips');
        $this->get(route('landing.index'))->assertOk()->assertSee(route('blog.index'));
        $this->post(route('blog.comments.store', $draft->slug), ['name' => 'Reader', 'body' => 'Draft comment'])->assertNotFound();
        $this->post(route('blog.comments.store', $published->slug), ['name' => 'Reader', 'body' => '<script>alert(1)</script>'])
            ->assertRedirect()->assertSessionHas('comment_success');
        $comment = BlogComment::firstOrFail();
        $this->assertTrue($comment->approved);
        $this->get(route('blog.show', $published->slug))->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->get(route('super_admin.blog.comments.index'))->assertOk()->assertSee('Reader');
        $this->patch(route('super_admin.blog.comments.update', $comment), ['approved' => 0])->assertRedirect();
        $this->get(route('blog.show', $published->slug))->assertDontSee('alert(1)');
        $this->patch(route('super_admin.blog.comments.update', $comment), ['approved' => 1])->assertRedirect();
        $this->get(route('blog.show', $published->slug))->assertSee('alert(1)');
        $this->delete(route('super_admin.blog.comments.destroy', $comment))->assertRedirect();
        $this->assertDatabaseMissing('blog_comments', ['id' => $comment->id]);
    }

    public function test_custom_slug_and_seo_metadata_are_validated_and_rendered(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $data = [
            'title' => 'Stock advice', 'slug' => 'stock-advice', 'category' => 'Inventory',
            'summary' => 'Default summary', 'content' => 'Article body', 'status' => 'published',
            'meta_title' => 'Inventory SEO', 'meta_description' => 'Tips & guidance', 'meta_keywords' => 'stock, inventory',
            'cover' => UploadedFile::fake()->image('seo-cover.jpg'),
        ];
        $this->actingAs($admin)->post(route('super_admin.blog.store'), $data)->assertRedirect();
        $post = BlogPost::where('slug', 'stock-advice')->firstOrFail();
        $this->get(route('blog.show', $post->slug))->assertOk()
            ->assertSee('<title>Inventory SEO | SmartProbook</title>', false)
            ->assertSee('<meta name="description" content="Tips &amp; guidance">', false)
            ->assertSee('<meta name="keywords" content="stock, inventory">', false)
            ->assertSee('<meta property="og:type" content="article">', false)
            ->assertSee(route('media.public', ['path' => $post->cover_path]));
        $this->get(route('super_admin.blog.edit', $post))->assertSee('stock-advice')->assertSee('Inventory SEO');

        unset($data['cover']);
        $this->post(route('super_admin.blog.store'), $data)->assertSessionHasErrors('slug');
        $data['slug'] = 'Invalid Slug';
        $this->put(route('super_admin.blog.update', $post), $data)->assertSessionHasErrors('slug');
        $data['slug'] = 'updated-stock-advice';
        $data['meta_title'] = null;
        $data['meta_description'] = null;
        $this->put(route('super_admin.blog.update', $post), $data)->assertRedirect();
        $this->get(route('blog.show', 'updated-stock-advice'))->assertOk()
            ->assertSee('<title>Stock advice | SmartProbook</title>', false)
            ->assertSee('<meta name="description" content="Default summary">', false);
        $this->getJson(route('blog.show', 'stock-advice'))->assertNotFound();
    }

    public function test_replies_are_public_immediately_and_must_target_a_visible_comment_on_the_same_post(): void
    {
        $post = BlogPost::create([
            'title' => 'Published', 'slug' => 'reply-post', 'category' => 'News',
            'summary' => 'News', 'content' => 'Content', 'status' => 'published', 'published_at' => now(),
        ]);
        $other = BlogPost::create([
            'title' => 'Other', 'slug' => 'other-post', 'category' => 'News',
            'summary' => 'News', 'content' => 'Content', 'status' => 'published', 'published_at' => now(),
        ]);
        $parent = $post->comments()->create(['name' => 'First', 'body' => 'Original']);
        $parent->approved = true;
        $parent->save();
        $otherComment = $other->comments()->create(['name' => 'Other', 'body' => 'Other comment']);
        $otherComment->approved = true;
        $otherComment->save();
        $hidden = $post->comments()->create(['name' => 'Hidden', 'body' => 'Hidden comment']);

        $this->post(route('blog.comments.store', $post->slug), [
            'name' => 'Responder', 'body' => '<b>Reply</b>', 'parent_id' => $parent->id, 'page' => 1,
        ])->assertRedirect(route('blog.show', ['slug' => $post->slug, 'page' => 1]) . '#comment-' . $parent->id);
        $reply = BlogComment::where('parent_id', $parent->id)->firstOrFail();
        $this->assertTrue($reply->approved);
        $this->get(route('blog.show', $post->slug))->assertSee('Responder')->assertSee('&lt;b&gt;Reply&lt;/b&gt;', false)->assertDontSee('<b>Reply</b>', false)->assertDontSee('Hidden comment');
        foreach ([$otherComment->id, $hidden->id, $reply->id] as $invalidParent) {
            $this->post(route('blog.comments.store', $post->slug), ['name' => 'Reader', 'body' => 'Invalid', 'parent_id' => $invalidParent])
                ->assertSessionHasErrors('parent_id');
        }
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->delete(route('super_admin.blog.comments.destroy', $parent))->assertRedirect();
        $this->assertDatabaseMissing('blog_comments', ['id' => $reply->id]);
    }

    public function test_only_super_admin_can_manage_posts_and_comments(): void
    {
        $post = BlogPost::create([
            'title' => 'Draft', 'slug' => 'draft', 'category' => 'News',
            'summary' => 'Draft', 'content' => 'Content', 'status' => 'draft',
        ]);
        $comment = $post->comments()->create(['name' => 'Reader', 'body' => 'Comment']);
        $this->get(route('super_admin.blog.index'))->assertRedirect();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->getJson(route('super_admin.blog.index'))->assertForbidden();
        $this->getJson(route('super_admin.blog.create'))->assertForbidden();
        $this->getJson(route('super_admin.blog.edit', $post))->assertForbidden();
        $this->postJson(route('super_admin.blog.store'), [])->assertForbidden();
        $this->putJson(route('super_admin.blog.update', $post), [])->assertForbidden();
        $this->deleteJson(route('super_admin.blog.destroy', $post))->assertForbidden();
        $this->patchJson(route('super_admin.blog.comments.update', $comment), ['approved' => 1])->assertForbidden();
        $this->deleteJson(route('super_admin.blog.comments.destroy', $comment))->assertForbidden();
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id]);
        $this->assertDatabaseHas('blog_comments', ['id' => $comment->id, 'approved' => false]);
    }

    public function test_post_and_comment_validation_and_comment_rate_limit(): void
    {
        $post = BlogPost::create([
            'title' => 'Published', 'slug' => 'published', 'category' => 'News',
            'summary' => 'News', 'content' => 'Content', 'status' => 'published', 'published_at' => now(),
        ]);
        $this->post(route('blog.comments.store', $post->slug), ['name' => '', 'body' => ''])
            ->assertSessionHasErrors(['name', 'body']);
        for ($i = 0; $i < 4; $i++) {
            $this->post(route('blog.comments.store', $post->slug), ['name' => 'Reader', 'body' => 'Comment'])->assertRedirect();
        }
        $this->post(route('blog.comments.store', $post->slug), ['name' => 'Reader', 'body' => 'Comment'])->assertStatus(429);
        $this->assertDatabaseCount('blog_comments', 4);

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->post(route('super_admin.blog.store'), ['title' => 'Bad', 'status' => 'published', 'cover' => UploadedFile::fake()->create('file.txt', 10)])
            ->assertSessionHasErrors(['category', 'summary', 'content', 'cover']);
        $this->assertDatabaseCount('blog_posts', 1);
    }

    public function test_formatted_content_is_sanitized_and_legacy_plain_text_is_preserved(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $data = [
            'title' => 'Formatted article', 'slug' => 'formatted-article', 'category' => 'News',
            'summary' => 'Summary', 'status' => 'published', 'content_format' => 'html',
            'content' => '<h2>Heading</h2><p style="text-align:center;color:#ff0000;font-size:18px"><strong>Bold</strong> <a href="https://example.com">safe link</a> <a href="javascript:alert(1)" onclick="alert(2)">link</a></p><table><tr><td>Cell</td></tr></table><script>alert(3)</script>',
        ];
        $this->actingAs($admin)->post(route('super_admin.blog.store'), $data)->assertRedirect();
        $post = BlogPost::where('slug', 'formatted-article')->firstOrFail();
        $this->assertSame('html', $post->content_format);
        $this->assertStringContainsString('<strong>Bold</strong>', $post->content);
        $this->assertStringContainsString('text-align:center', $post->content);
        $this->assertStringContainsString('font-size:18px', $post->content);
        $this->assertStringContainsString('href="https://example.com"', $post->content);
        $this->assertStringContainsString('<table>', $post->content);
        $this->assertStringNotContainsString('javascript:', $post->content);
        $this->assertStringNotContainsString('onclick', $post->content);
        $this->assertStringNotContainsString('<script>', $post->content);
        $this->get(route('blog.show', $post->slug))->assertOk()
            ->assertSee('<h2>Heading</h2>', false)->assertSee('<strong>Bold</strong>', false)
            ->assertDontSee('javascript:alert(1)', false);
        $this->get(route('super_admin.blog.edit', $post))->assertOk()
            ->assertSee('summernote-lite.min.js')->assertSee('blog-editor.js');

        $data['content'] = '<p>&nbsp;</p><script>alert(1)</script>';
        $this->put(route('super_admin.blog.update', $post), $data)->assertSessionHasErrors('content');
        $this->assertStringContainsString('<strong>Bold</strong>', $post->fresh()->content);

        $legacy = BlogPost::create([
            'title' => 'Legacy', 'slug' => 'legacy-text', 'category' => 'News',
            'summary' => 'Summary', 'content' => "<strong>Literal</strong>\nSecond line",
            'status' => 'published', 'published_at' => now(),
        ]);
        $this->get(route('blog.show', $legacy->slug))->assertOk()
            ->assertSee('&lt;strong&gt;Literal&lt;/strong&gt;', false)->assertDontSee('<strong>Literal</strong>', false)
            ->assertSee('Second line');

        $post->update(['content' => '<p>Safe</p><img src=x onerror=alert(9)>']);
        $this->get(route('blog.show', $post->slug))->assertOk()->assertDontSee('onerror=alert(9)', false);
    }

    public function test_six_starter_articles_are_published_with_seo_metadata_and_covers(): void
    {
        $this->seed(BlogStarterPostsSeeder::class);
        $this->assertDatabaseCount('blog_posts', 6);
        foreach (BlogPost::all() as $post) {
            $this->assertSame('published', $post->status);
            $this->assertSame('html', $post->content_format);
            $this->assertNotNull($post->published_at);
            $this->assertNotEmpty($post->meta_title);
            $this->assertNotEmpty($post->meta_description);
            $this->assertNotEmpty($post->meta_keywords);
            Storage::disk('public')->assertExists($post->cover_path);
            $this->assertStringContainsString('<svg', Storage::disk('public')->get($post->cover_path));
            $this->get(route('media.public', ['path' => $post->cover_path]))->assertOk();
            $this->get(route('blog.show', $post->slug))->assertOk();
        }
        $post = BlogPost::firstOrFail();
        $cover = $post->cover_path;
        $post->update(['title' => 'Edited by an administrator']);
        $this->seed(BlogStarterPostsSeeder::class);
        $this->assertDatabaseCount('blog_posts', 6);
        $this->assertSame('Edited by an administrator', $post->fresh()->title);
        $this->assertSame($cover, $post->fresh()->cover_path);
    }

    public function test_public_blog_has_featured_related_articles_metadata_and_share_links(): void
    {
        $author = User::factory()->create(['role' => 'super_admin', 'name' => 'Editorial Author']);
        $featured = BlogPost::create([
            'title' => 'Featured guide', 'slug' => 'featured-guide', 'category' => 'Operations',
            'summary' => 'A featured summary.', 'content' => str_repeat('useful words ', 220),
            'status' => 'published', 'published_at' => now(),
        ]);
        $featured->author()->associate($author);
        $featured->save();
        foreach ([
            ['Related guide one', now()->subDay()],
            ['Related guide two', now()->subDays(2)],
            ['Related guide three', now()->subDays(3)],
        ] as [$title, $publishedAt]) {
            BlogPost::create([
                'title' => $title, 'slug' => \Illuminate\Support\Str::slug($title), 'category' => 'Operations',
                'summary' => 'Related summary.', 'content' => 'Related article body.',
                'status' => 'published', 'published_at' => $publishedAt,
            ]);
        }
        BlogPost::create([
            'title' => 'Unrelated guide', 'slug' => 'unrelated-guide', 'category' => 'Finance',
            'summary' => 'Unrelated summary.', 'content' => 'Unrelated article body.',
            'status' => 'published', 'published_at' => now()->subHours(2),
        ]);

        $this->get(route('blog.index'))->assertOk()
            ->assertSee('Featured guide')->assertSee('Editorial Author')
            ->assertSee('2 min read')->assertSee('Latest articles');
        $this->get(route('blog.show', $featured->slug))->assertOk()
            ->assertSee('Editorial Author')->assertSee('2 min read')
            ->assertSee('Related guide one')->assertSee('Related guide two')->assertSee('Related guide three')
            ->assertDontSee('Unrelated guide')
            ->assertSee('linkedin.com/sharing/share-offsite')
            ->assertSee('twitter.com/intent/tweet')
            ->assertSee('wa.me/');
    }

    public function test_super_admin_can_filter_blog_posts_by_search_status_and_category(): void
    {
        BlogPost::create([
            'title' => 'Operations playbook', 'slug' => 'operations-playbook', 'category' => 'Operations',
            'summary' => 'Planning for teams.', 'content' => 'Content', 'status' => 'published', 'published_at' => now(),
        ]);
        BlogPost::create([
            'title' => 'Operations draft', 'slug' => 'operations-draft', 'category' => 'Operations',
            'summary' => 'Planning draft.', 'content' => 'Content', 'status' => 'draft',
        ]);
        BlogPost::create([
            'title' => 'Finance playbook', 'slug' => 'finance-playbook', 'category' => 'Finance',
            'summary' => 'Planning for finance.', 'content' => 'Content', 'status' => 'published', 'published_at' => now(),
        ]);

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))
            ->get(route('super_admin.blog.index', [
                'search' => 'playbook',
                'status' => 'published',
                'category' => 'Operations',
            ]))
            ->assertOk()
            ->assertSee('Operations playbook')
            ->assertDontSee('Operations draft')
            ->assertDontSee('Finance playbook');
    }
}
