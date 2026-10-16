<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgressUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'updated_by_id',
        // 'progress', // optional decimal 0-100
        'notes',
    ];

    // protected $casts = [
    //     'progress' => 'decimal:2',
    // ];

    /**
     * Project this update belongs to
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * User who made the update
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
