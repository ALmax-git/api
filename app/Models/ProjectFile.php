<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'file_system_item_id',
        'description',
    ];

    /**
     * Project this file belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * The actual file record
     */
    public function fileSystemItem(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class);
    }
}
