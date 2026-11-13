<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:100',
        ]);
        $hasFilters = filled($filters['search'] ?? null) || filled($filters['category'] ?? null);
        $featuredPost = $hasFilters
            ? null
            : BlogPost::published()->with('author')->orderByDesc('published_at')->orderByDesc('id')->first();
        $postsQuery = BlogPost::published()->with('author')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when(filled($filters['category'] ?? null), fn ($query) => $query->where('category', $filters['category']))
            ->when($featuredPost, fn ($query) => $query->whereKeyNot($featuredPost->id))
            ->orderByDesc('published_at')->orderByDesc('id');
        $posts = $postsQuery->paginate(9)->withQueryString();
        $categories = BlogPost::published()->whereNotNull('category')->where('category', '!=', '')
            ->select('category')->distinct()->orderBy('category')->pluck('category');

        $seoTitle = 'SmartProbook Blog';
        $seoDescription = 'Practical business insights, product guidance, and SmartProbook updates for growing teams.';
        $seoKeywords = 'business insights, accounting, inventory management, business growth, SmartProbook';
        $seoImage = $featuredPost?->cover_path
            ? route('media.public', ['path' => $featuredPost->cover_path])
            : asset('assets/img/logos.png');
        $seoCanonical = route('blog.index');

        return view('blog.index', compact('posts', 'categories', 'featuredPost', 'seoTitle', 'seoDescription', 'seoKeywords', 'seoImage', 'seoCanonical'));
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()->with('author')->where('slug', $slug)->firstOrFail();
        $relatedPosts = BlogPost::published()->with('author')->whereKeyNot($post->id)
            ->orderByRaw('CASE WHEN category = ? THEN 0 ELSE 1 END', [$post->category])
            ->orderByDesc('published_at')->orderByDesc('id')->limit(3)->get();
        $comments = $post->comments()->whereNull('parent_id')->where('approved', true)
            ->with(['replies' => fn ($query) => $query->where('approved', true)->orderBy('created_at')->orderBy('id')])
            ->orderBy('created_at')->orderBy('id')->paginate(20);
        $seoType = 'article';
        $seoImage = $post->cover_path ? route('media.public', ['path' => $post->cover_path]) : null;
        $seoTitle = $post->meta_title ?: $post->title;
        $seoDescription = \Illuminate\Support\Str::limit(
            trim(strip_tags($post->meta_description ?: $post->summary)),
            160,
            ''
        );
        $seoKeywords = $post->meta_keywords;
        $seoCanonical = route('blog.show', $post->slug);

        return view('blog.show', compact('post', 'relatedPosts', 'comments', 'seoType', 'seoImage', 'seoTitle', 'seoDescription', 'seoKeywords', 'seoCanonical'));
    }

    public function comment(Request $request, string $slug)
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'body' => 'required|string|max:2000',
            'parent_id' => ['nullable', 'integer', Rule::exists('blog_comments', 'id')->where(fn ($query) => $query
                ->where('blog_post_id', $post->id)->whereNull('parent_id')->where('approved', true))],
            'page' => 'nullable|integer|min:1',
        ]);
        $comment = new \App\Models\BlogComment(['name' => $data['name'], 'body' => $data['body']]);
        $comment->parent_id = $data['parent_id'] ?? null;
        $comment->approved = true;
        $post->comments()->save($comment);

        $page = $comment->parent_id
            ? ($data['page'] ?? 1)
            : max(1, (int) ceil($post->comments()->whereNull('parent_id')->where('approved', true)->count() / 20));

        return redirect()->to(route('blog.show', ['slug' => $post->slug, 'page' => $page]) . '#comment-' . ($comment->parent_id ?? $comment->id))
            ->with('comment_success', 'Thank you. Your comment is now public.');
    }
}
