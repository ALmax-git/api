<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BotCommand extends Model
{
    protected $fillable = [
        'bot_id',
        'command',
        'response',
    ];
    /**
     * Get the bot that owns the command.
     */
    public function bot()
    {
        return $this->belongsTo(Bot::class);
    }
}
