<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractSigner extends Model
{
    protected $fillable = [
        'contract_id',
        'user_id',
        'file_system_item_id',
        'client_id',
        'role',
        'signing_order',
        'status',
        'is_signed',
        'follow_order',
        'notified_at',
        'viewed_at',
        'signed_at',
        'rejected_at',
        'signature',
    ];
    protected $casts = [
        'is_signed' => 'boolean',
        'follow_order' => 'boolean',
        'notified_at' => 'datetime',
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];
    /* |-------------------------------------------------------------------------- | Signer Roles |-------------------------------------------------------------------------- */
    public const ROLE_SIGNER = 'signer';
    public const ROLE_WITNESS = 'witness';
    public const ROLE_APPROVER = 'approver';
    /* |-------------------------------------------------------------------------- | Signer Status |-------------------------------------------------------------------------- */
    public const STATUS_PENDING = 'pending';
    public const STATUS_NOTIFIED = 'notified';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    /* |-------------------------------------------------------------------------- | Relationships |-------------------------------------------------------------------------- */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    public function file(): BelongsTo
    {
        return $this->belongsTo(FileSystemItem::class, 'file_system_item_id');
    }
    /* |-------------------------------------------------------------------------- | Helpers |-------------------------------------------------------------------------- */
    public function isSigned(): bool
    {
        return $this->is_signed;
    }
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
    public function canSign(): bool
    {
        return ! $this->is_signed && ! $this->isRejected() && $this->status !== self::STATUS_EXPIRED;
    }
}
