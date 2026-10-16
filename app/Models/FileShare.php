<?php
// app/Models/FileShare.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileShare extends Model
{
    protected $fillable = [
        'file_system_item_id',
        'user_id',
        'permission',
        'expires_at',
        'share_token',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class, 'file_system_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
