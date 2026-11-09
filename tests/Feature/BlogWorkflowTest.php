<?php

namespace Tests\Feature;

use App\Http\Middleware\EnforceDeviceSessionLimit;
use App\Http\Middleware\ForceLogoutExpiredSession;
use App\Http\Middleware\VerifyTenantSession;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Models\User;
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
        $this->get(route('super_admin.blog.edit', $post))->assertOk()->assertSee('First line');
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
}
