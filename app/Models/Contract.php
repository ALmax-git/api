<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'description',
        'title',
        'requires_verification',
        'verified',
        'conditions',
        'events_log',
        'system_signature',
        'status',
    ];
    protected $casts = [
        'requires_verification' => 'boolean',
        'verified' => 'boolean',
        'conditions' => 'array',
        'events_log' => 'array',
    ];

    /* |-------------------------------------------------------------------------- | Contract Status |-------------------------------------------------------------------------- */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_SIGNING = 'signing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ARCHIVED = 'archived'; /* |-------------------------------------------------------------------------- | Relationships |-------------------------------------------------------------------------- */
    // AUTO-GENERATE UUID WHEN CREATING
    protected static function boot()
    {
        parent::boot();

        // When creating, the model ID is not yet available. Set a temporary UUID
        // to ensure the field is populated. After creation (when ID exists),
        // generate the final UUID using make_uuid which requires the int ID.
        static::creating(function ($contract) {
            if (!$contract->uuid) {
                // $contract->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });

        static::created(function ($contract) {
            if ($contract->id && !$contract->uuid_changed ?? true) {
                // If make_uuid depends on the integer ID, generate and persist it now.
                $contract->uuid = make_uuid($contract->id, 'contract');
            }
        });
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function files(): HasMany
    {
        return $this->hasMany(ContractFile::class);
    }
    public function signers(): HasMany
    {
        return $this->hasMany(ContractSigner::class);
    } /* |-------------------------------------------------------------------------- | Helpers |-------------------------------------------------------------------------- */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
    public function isSigning(): bool
    {
        return $this->status === self::STATUS_SIGNING;
    }
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
