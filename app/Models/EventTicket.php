<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTicket extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'max_tickets',
        'is_active',
        'sale_starts_at',
        'sale_ends_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
    ];

    // --- Relationships ---

    /**
     * Get the event that owns the ticket type.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the individual tickets created from this type.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class); // Assuming your main ticket model is EventTicket
    }

    // --- Accessors/Helpers (Optional but Recommended) ---

    /**
     * Helper to get the number of tickets sold for this type.
     */
    public function ticketsSoldCount()
    {
        // Counts only the tickets that have not been soft deleted or refunded
        return $this->tickets()
            ->where('is_refunded', false)
            ->count();
    }
}
