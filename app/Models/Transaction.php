<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'wallet_id',
        '_wallet_id',
        'asset_id',
        'from_address',
        'to_address',
        'status',
        'tx_ref',
        'hash',
        'description',
        'network_fee',
        'type',
        'amount',
        'fiat_amount',
        'fiat_currency',
        'prev_hash',
        'block_number',
        'explorer_url',
        'metadata',
    ];

    /*==========================================
    | CASTING
    ==========================================*/
    protected $casts = [
        'status'        => TransactionStatus::class,
        'type'          => TransactionType::class,
        'amount'        => 'decimal:18',
        'fiat_amount'   => 'decimal:2',
        'metadata'      => 'array',
    ];

    /*==========================================
    | RELATIONSHIPS
    ==========================================*/
    public function wallet()
    {
        return $this->belongsTo(Wallet::class, 'wallet_id');
    }

    public function _wallet()
    {
        return $this->belongsTo(Wallet::class, '_wallet_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // public function network()
    // {
    //     return $this->belongsTo(Network::class);
    // }

    /*==========================================
    | SCOPES
    ==========================================*/
    public function scopePending($query)
    {
        return $query->where('status', TransactionStatus::PENDING);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', TransactionStatus::SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', TransactionStatus::FAILED);
    }

    public function scopeIncoming($query, $walletId)
    {
        return $query->where('_wallet_id', $walletId);
    }

    public function scopeOutgoing($query, $walletId)
    {
        return $query->where('wallet_id', $walletId);
    }

    /*==========================================
    | HELPERS
    ==========================================*/
    public function isInternal(): bool
    {
        return !is_null($this->wallet_id) && !is_null($this->_wallet_id);
    }

    public function isExternal(): bool
    {
        return is_null($this->wallet_id) || is_null($this->_wallet_id);
    }

    public function markAs(TransactionStatus $status): void
    {
        $this->update(['status' => $status->value]);
    }

    public function explorer(): ?string
    {
        return $this->explorer_url ?? null;
    }

    // GENERATE NEW HASH FOR TRANSACTION CHAINING
    public static function NewHash(Mixed $payload): string
    {
        return hash('sha256', json_encode($payload));
    }

    // GET LAST TRANSACTION HASH
    public static function last_hash(): ?string
    {
        return self::orderBy('id', 'desc')->value('hash');
    }
}
