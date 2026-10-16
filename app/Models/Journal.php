<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'title',
        'note',
        'date'
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    /**
     * Get the client that owns the journal.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the journal.
     */
    public function user(): BelongsTo
    {
        // Assuming a User model exists
        return $this->belongsTo(User::class);
    }

    /**
     * Get the entries for the journal.
     */
    public function entries(): HasMany
    {
        return $this->hasMany(JournalEntry::class)->latest('date');
    }
    public function scopePersonal($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->whereNull('client_id');
    }

    public function scopeClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }
}
