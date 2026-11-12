<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogPost extends Model
{
    protected $fillable = ['author_id', 'title', 'slug', 'category', 'summary', 'content', 'content_format', 'cover_path', 'status', 'published_at', 'meta_title', 'meta_description', 'meta_keywords'];

    protected $casts = ['published_at' => 'datetime'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getReadingTimeMinutesAttribute(): int
    {
        $content = html_entity_decode(strip_tags((string) $this->content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $wordCount = preg_match_all('/[\p{L}\p{N}]+/u', $content, $matches);

        return max(1, (int) ceil(($wordCount ?: 0) / 220));
    }
}
