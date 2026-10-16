<?php

namespace App\Models;

use App\Enums\CommentStatus;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    protected $casts = [
        'status' => CommentStatus::class,
    ];
    protected $fillable = [
        'author_id',
        'content',
        'status'
    ];

    /* 🔗 Relationships */
    public function user()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_comment')
            ->withTimestamps();
    }
}
