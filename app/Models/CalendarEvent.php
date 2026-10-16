<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{

    protected $fillable = [
        'title',
        'url',
        'start',
        'end',
        'user_id',
        'client_id',
        'status', # ['pending', 'completed']
        'type', # ['meeting', 'reminder' 'event', 'task']
        'visibility',  # ['public', 'private', 'internal']
        'background',  # Default color for the event
    ];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
        'status' => 'string',
        'type' => 'string',
        'visibility' => 'string',
    ];

    // 🧠 Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // 🎯 Scopes
    public function scopeForUserOrClient($query, $userId, $clientId = null)
    {
        return $query->where(function ($q) use ($userId, $clientId) {
            $q->where('user_id', $userId);

            if ($clientId) {
                $q->orWhere('client_id', $clientId);
            }
        });
    }

    // 🎨 Helpful getter (if needed in frontend)
    public function getColorAttribute()
    {
        return $this->background ?? '#000000';
    }
}
