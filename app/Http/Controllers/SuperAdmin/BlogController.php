<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use App\Models\BlogPost;
use App\Support\BlogHtml;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:draft,published',
            'category' => 'nullable|string|max:100',
        ]);
        $posts = $this->postsForManagement($filters);
        $categories = $this->categoriesForManagement();
        $stats = [
            'total' => BlogPost::count(),
            'published' => BlogPost::where('status', 'published')->count(),
            'drafts' => BlogPost::where('status', 'draft')->count(),
            'comments' => BlogComment::count(),
        ];

        return view('SuperAdmin.blog.index', compact('posts', 'categories', 'filters', 'stats'));
    }

    public function create()
    {
        return view('SuperAdmin.blog.form', [
            'post' => new BlogPost(),
            'posts' => $this->postsForManagement(),
            'categories' => $this->categoriesForManagement(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: $this->uniqueSlug($data['title']);
        $data['author_id'] = $request->user()->id;
        $data['published_at'] = $data['status'] === 'published' ? now() : null;
        $data['cover_path'] = $this->storeCover($request);
        try {
            $post = BlogPost::create($data);
        } catch (\Throwable $exception) {
            if ($data['cover_path']) {
                $this->deleteCover($data['cover_path']);
            }
            throw $exception;
        }

        return redirect()->route('super_admin.blog.edit', $post)->with('success', 'Blog post created.');
    }

    public function edit(BlogPost $post)
    {
        return view('SuperAdmin.blog.form', [
            'post' => $post,
            'posts' => $this->postsForManagement(),
            'categories' => $this->categoriesForManagement(),
        ]);
    }

    public function update(Request $request, BlogPost $post)
    {
        $data = $this->validated($request, $post);
        $data['slug'] = $data['slug'] ?? $post->slug;
        $data['published_at'] = $data['status'] === 'published' ? ($post->published_at ?? now()) : null;
        $oldCover = $post->cover_path;
        $newCover = $this->storeCover($request);
        if ($newCover || $request->boolean('remove_cover')) {
            $data['cover_path'] = $newCover;
        }
        try {
            $post->update($data);
        } catch (\Throwable $exception) {
            if ($newCover) {
                $this->deleteCover($newCover);
            }
            throw $exception;
        }
        if ($oldCover && ($newCover || $request->boolean('remove_cover'))) {
            $this->deleteCover($oldCover);
        }

        return redirect()->route('super_admin.blog.edit', $post)->with('success', 'Blog post updated.');
    }

    public function destroy(BlogPost $post)
    {
        $cover = $post->cover_path;
        $post->delete();
        if ($cover) {
            $this->deleteCover($cover);
        }

        return redirect()->route('super_admin.blog.index')->with('success', 'Blog post and its comments deleted.');
    }

    public function comments()
    {
        $comments = BlogComment::with('post')->latest()->orderByDesc('id')->paginate(20);

        return view('SuperAdmin.blog.comments', compact('comments'));
    }

    public function updateComment(Request $request, BlogComment $comment)
    {
        $data = $request->validate(['approved' => 'required|boolean']);
        $comment->approved = $data['approved'];
        $comment->save();

        return back()->with('success', $comment->approved ? 'Comment visible.' : 'Comment hidden.');
    }

    public function destroyComment(BlogComment $comment)
    {
        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    private function postsForManagement(array $filters = []): LengthAwarePaginator
    {
        return BlogPost::withCount('comments')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when(filled($filters['category'] ?? null), fn ($query) => $query->where('category', $filters['category']))
            ->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)
            ->withPath(route('super_admin.blog.index'));
    }

    private function categoriesForManagement()
    {
        return BlogPost::query()->whereNotNull('category')->where('category', '!=', '')
            ->select('category')->distinct()->orderBy('category')->pluck('category');
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::limit(Str::slug($title) ?: 'post', 180, '');
        $slug = $base;
        $suffix = 2;

        while (BlogPost::where('slug', $slug)->exists()) {
            $slug = Str::limit($base, 180 - strlen((string) $suffix) - 1, '') . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function validated(Request $request, ?BlogPost $post = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'slug' => ['nullable', 'string', 'max:200', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('blog_posts', 'slug')->ignore($post?->id)],
            'category' => 'required|string|max:100',
            'summary' => 'required|string|max:500',
            'content' => 'required|string|max:100000',
            'content_format' => 'nullable|in:text,html',
            'status' => 'required|in:draft,published',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_cover' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:200',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
        ]);

        $data['content_format'] = $data['content_format'] ?? $post?->content_format ?? 'text';
        if ($data['content_format'] === 'html') {
            $data['content'] = app(BlogHtml::class)->clean($data['content']);
            $text = html_entity_decode(strip_tags($data['content']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if (preg_replace('/[\s\x{00a0}\x{200b}]+/u', '', $text) === '') {
                throw ValidationException::withMessages(['content' => 'Article content cannot be empty.']);
            }
        }

        return $data;
    }

    private function storeCover(Request $request): ?string
    {
        if (!$request->hasFile('cover')) {
            return null;
        }
        $path = $request->file('cover')->store('blog/covers', 'public');
        if (!$path) {
            throw new RuntimeException('Unable to store the blog cover image.');
        }

        return $path;
    }

    private function deleteCover(string $path): void
    {
        if (!Storage::disk('public')->delete($path)) {
            Log::warning('Unable to delete blog cover image.', ['path' => $path]);
        }
    }
}
