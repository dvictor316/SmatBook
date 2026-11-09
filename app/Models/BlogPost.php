<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogPost extends Model
{
    protected $fillable = ['author_id', 'title', 'slug', 'category', 'summary', 'content', 'cover_path', 'status', 'published_at', 'meta_title', 'meta_description', 'meta_keywords'];

    protected $casts = ['published_at' => 'datetime'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }
}
