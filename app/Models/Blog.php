<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Blog extends Model
{
    use HasFactory;

    protected $casts = [
        'tags' => 'array',
        'meta' => 'array',
        'publish_at' => 'datetime',
        'is_featured' => 'boolean',
        'status' => \App\Enums\BlogStatus::class,
    ];

    protected $fillable = [
        'author_id',
        'client_id',
        'title',
        'sub_title',
        'content',
        'tags',
        'status',
        'publish_at',
        'cover_picture_id',
        'slug',
        'excerpt',
        'meta',
        'is_featured',
        'read_time',
        'language',
    ];


    /* 🔗 Relationships */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments()
    {
        return $this->belongsToMany(Comment::class, 'blog_comment')
            ->withTimestamps();
    }

    public function cover_picture()
    {
        return $this->belongsTo(FileSystemItem::class, 'cover_picture_id');
    }

    /* 🎯 Helpers */
    protected static function booted()
    {
        static::creating(function ($blog) {
            if (! $blog->slug) {
                $blog->slug = Str::slug($blog->title) . '-' . make_uuid($blog->id ?? rand(1, 999), 'blog');
            }
            if (! $blog->excerpt) {
                $blog->excerpt = Str::limit(strip_tags($blog->content), 160);
            }
            if (! $blog->read_time) {
                $words = str_word_count(strip_tags($blog->content));
                $blog->read_time = ceil($words / 200); // avg 200 wpm
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            });
    }
}
