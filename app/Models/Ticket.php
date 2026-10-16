<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    // Use SoftDeletes trait to handle the softDeletes column
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tickets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event_id',
        'event_ticket_id', // Renamed from ticket_type_id
        'order_id',
        'qr_code',
        'section',
        'row',
        'seat_number',
        'scanned_by_user_id',
        'used_at',
        'qr_expires_at',
        'is_refunded',
        'refund_amount',
        'refunded_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_refunded' => 'boolean',
        'refund_amount' => 'decimal:2',
        'used_at' => 'datetime',
        'qr_expires_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    // --------------------------------------------------------------------------
    // 🔑 Relationships
    // --------------------------------------------------------------------------

    /**
     * Get the user who owns/purchased the ticket (may be null for guests).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the event the ticket is for.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    /**
     * Get the specific ticket type details (price, name, etc.) this ticket belongs to.
     * NOTE: This assumes you created a model named EventTicket for the table previously called 'ticket_types'.
     */
    public function type(): BelongsTo
    {
        // Assuming your 'ticket_types' table is now represented by the 'EventTicket' model
        return $this->belongsTo(EventTicket::class, 'event_ticket_id');
    }

    /**
     * Get the order the ticket was purchased under.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id'); // Assumes an Order model exists
    }

    /**
     * Get the user who scanned the ticket at the event.
     */
    public function scannedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }
}
