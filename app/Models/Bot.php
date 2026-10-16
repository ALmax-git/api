<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bot extends Model
{
    protected $fillable = [
        'client_id',
        'created_by_id',
        'updated_by_id',
        'assigned_user_id',
        'name',
        'device_key',
        'mac_address',
        'status',
        'ip_address',
        'location',
        'last_seen_at',
        'metadata',
    ];
    protected $casts = [
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
    ];
    /**
     * Get the client that owns the bot.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the bot.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
    /**
     * Get the user who last updated the bot.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }


    public function asign_user()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
    /**
     * Get the payloads associated with the bot.
     */
    public function payloads()
    {
        return $this->hasMany(BotPayload::class);
    }
    /**
     * Get the commands associated with the bot.
     */
    public function commands()
    {
        return $this->hasMany(BotCommand::class);
    }
    /**
     * Get the event logs associated with the bot.
     */
    public function events()
    {
        return $this->hasMany(BotEvent::class);
    }
}
