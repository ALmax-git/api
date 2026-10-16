<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotPayload extends Model
{
    protected $fillable = [
        'bot_id',
        'created_by_id',
        'updated_by_id',
        'name',
        'pin_number',
        'pin_value',
        'metadata',
    ];
    protected $casts = [
        'metadata' => 'array',
    ];
    /**
     * Get the bot that owns the payload.
     */
    public function bot()
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Get the user who created the payload.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
    /**
     * Get the user who last updated the payload.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }
}
