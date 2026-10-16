<?php
// app/Models/FileSystemItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FileSystemItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'path',
        'type',
        'parent_id',
        'owner_id',
        'mime_type',
        'size',
        'metadata',
        'disk',
        'storage_path',
        'thumbnail_path',
        'hash',
        'is_public',
        'is_hidden',
        'is_favorite',
        'last_accessed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'size' => 'integer',
        'is_public' => 'boolean',
        'is_favorite' => 'boolean',
        'last_accessed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FileSystemItem::class, 'parent_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(FileShare::class);
    }

    // public function versions(): HasMany
    // {
    //     return $this->hasMany(FileVersion::class);
    // }

    // public function tags(): BelongsToMany
    // {
    //     return $this->belongsToMany(FileTag::class, 'file_system_item_tag');
    // }

    // Scopes
    public function scopeFiles($query)
    {
        return $query->where('type', 'file');
    }

    public function scopeFolders($query)
    {
        return $query->where('type', 'folder');
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('owner_id', $userId);
    }

    public function scopeSharedWith($query, $userId)
    {
        return $query->whereHas('shares', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    // Methods
    public function isFolder(): bool
    {
        return $this->type === 'folder';
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    public function getFullPath(): string
    {
        return $this->path . '/' . $this->name;
    }

    public function markAccessed(): void
    {
        $this->update(['last_accessed_at' => now()]);
    }

    public function is_image(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function getHumanReadableSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = $this->size;
        $factor = (int) floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . $units[$factor];
    }
}
