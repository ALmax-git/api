<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotEvent extends Model
{
    protected $fillable = [
        'bot_id',
        'event_type',
        'payload',
    ];
    protected $casts = [
        'payload' => 'array',
    ];
    /**
     * Get the bot that owns the event.
     */
    public function bot()
    {
        return $this->belongsTo(Bot::class);
    }
}
