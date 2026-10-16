<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveLevelRule extends Model
{
    protected $fillable = [
        'leave_id',
        'level_id',
        'days_interval',
        'number_of_days',
    ];

    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
}
