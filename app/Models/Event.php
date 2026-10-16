<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'organizer_id',
        'client_id',
        'city_id',
        'state_id',
        'country_id',
        'title',
        'description',
        'location',
        'price',
        'capacity',
        'max_attendees',
        'min_age',
        'priority',
        'is_online',
        'ticketing_enabled',
        'is_recurring',
        'featured',
        'tags',
        'meta',
        'timezone',
        'visibility',
        'contact_email',
        'contact_phone',
        'external_url',
        'status',
        'sale_starts_at',
        'sale_ends_at',
        'recurrence_end_at',
        'published_at',
        'cancelled_at',
        'start_time',
        'end_time',
        'starting_day',
        'closing_day',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        // Boolean casts
        'is_online' => 'boolean',
        'ticketing_enabled' => 'boolean',
        'is_recurring' => 'boolean',
        'featured' => 'boolean',

        // Date/Time casts
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'recurrence_end_at' => 'datetime',
        'published_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'starting_day' => 'datetime',
        'closing_day' => 'datetime',

        // JSON and Decimal casts
        'tags' => 'array',
        'meta' => 'array',
        'price' => 'decimal:2',

        // Enum Cast (Requires Laravel 9+ and the Enum setup)
        'status' => EventStatus::class,
    ];

    // --------------------------------------------------------------------------
    // 🔑 Relationships
    // --------------------------------------------------------------------------

    /**
     * The user who created this event record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The primary organizer responsible for running the event (often a different role than the creator).
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * The client or company hosting the event.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id'); // Assumes a Client model exists
    }

    /**
     * The city where the event is located.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id'); // Assumes a City model exists
    }

    /**
     * The state/province where the event is located.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id'); // Assumes a State model exists
    }

    /**
     * The country where the event is located.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id'); // Assumes a Country model exists
    }

    /**
     * Get all ticket types available for this event.
     */
    public function event_tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class); // Links to the model we just created
    }

    /**
     * Get all individual tickets sold for this event.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(EventTicket::class); // Links to the main ticket model
    }
}
