<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'order',
        'title',
    ];
    // Get all users associated with this level through user_clients pivot
    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,          // Target model
            UserClient::class,    // Intermediate model
            'level_id',          // Foreign key on intermediate table
            'id',                // Foreign key on target table
            'id',                // Local key on this model
            'user_id'            // Local key on intermediate table
        )->where('user_clients.client_id', $this->client_id);
    }

    public function leaves()
    {
        return $this->hasManyThrough(
            Leave::class,
            LeaveLevelRule::class,
            'level_id', // Foreign key on LeaveLevel table
            'id', // Foreign key on Level table
            'id', // Local key on Leave table
            'leave_id' // Local key on LeaveLevel table
        );
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function client_users(): HasMany
    {
        return $this->hasMany(UserClient::class);
    }
}
