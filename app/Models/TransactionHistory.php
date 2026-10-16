<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;

class TransactionHistory extends Model
{
    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'status',
        'tx_ref',
        'currency',
        'description',
        'close_at',
    ];

    // Cast fields to native types
    protected $casts = [
        'status'                => TransactionStatus::class,
        'type'                  => TransactionType::class,
        'amount'                => 'double',
        'close_at'              => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];
    /**
     * Relationship: TransactionHistory belongs to a Wallet.
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Scope to filter by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
