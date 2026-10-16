<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRoster extends Model
{
    protected $fillable = [
        'user_id',
        'leave_id',
        'status',
        'frequency', //indicate s how many times the leave is taken
        'starting_date',
        'resuming_date',
        'comment',
    ];
    // LeaveRoster.php
    protected $casts = [
        'starting_date' => 'date',
        'resuming_date' => 'date',
        'status' => 'string',
        'frequency' => 'integer',
        'comment' => 'string',
    ];

    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
