<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;

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
        $comments = $post->comments()->where('approved', true)->orderBy('created_at')->orderBy('id')->paginate(20);

        return view('blog.show', compact('post', 'comments'));
    }

    public function comment(Request $request, string $slug)
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'body' => 'required|string|max:2000',
        ]);
        $post->comments()->create($data);

        return redirect()->route('blog.show', $post->slug)->with('comment_success', 'Thank you. Your comment will appear after approval.');
    }
}
