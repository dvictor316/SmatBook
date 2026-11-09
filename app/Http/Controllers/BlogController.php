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
        $posts = BlogPost::published()
            ->when(isset($filters['search']), function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['category']), fn ($query) => $query->where('category', $filters['category']))
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(9)->withQueryString();
        $categories = BlogPost::published()->select('category')->distinct()->orderBy('category')->pluck('category');

        return view('blog.index', compact('posts', 'categories'));
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        $comments = $post->comments()->whereNull('parent_id')->where('approved', true)
            ->with(['replies' => fn ($query) => $query->where('approved', true)->orderBy('created_at')->orderBy('id')])
            ->orderBy('created_at')->orderBy('id')->paginate(20);
        $seoType = 'article';
        $seoImage = $post->cover_path ? route('media.public', ['path' => $post->cover_path]) : null;
        $seoTitle = $post->meta_title ?: $post->title;
        $seoDescription = $post->meta_description ?: $post->summary;
        $seoKeywords = $post->meta_keywords;

        return view('blog.show', compact('post', 'comments', 'seoType', 'seoImage', 'seoTitle', 'seoDescription', 'seoKeywords'));
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
