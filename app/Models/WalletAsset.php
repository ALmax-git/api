<?php

namespace App\Models;

use App\Enums\WalletAssetStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'wallet_assets';

    protected $fillable = [
        'wallet_id',
        'asset_id',
        'balance',
        'address',
        'locked_balance',
        'pending_balance',
        'metadata',
        'status',
    ];

    protected $casts = [
        'balance'         => 'float',
        'locked_balance'  => 'decimal:8',
        'pending_balance' => 'decimal:8',
        'metadata'        => 'array',
        'status'                => WalletAssetStatus::class,
    ];

    /**
     * RELATIONSHIPS
     */

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    // /**
    //  * ACCESSORS
    //  */

    // public function getAvailableBalanceAttribute()
    // {
    //     return max(
    //         $this->balance - $this->locked_balance - $this->pending_balance,
    //         0
    //     );
    // }

    /**
     * ACCESSORS
     */

    public function getAvailableBalanceAttribute()
    {
        $balance  = 0;
        $balance += asset_value($this->asset, $this->balance - $this->locked_balance);
        return max($balance, 0);
    }
    /**
     * SCOPES
     */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    public function scopeByWallet($query, $walletId)
    {
        return $query->where('wallet_id', $walletId);
    }

    /**
     * HELPERS — safe financial operations
     */

    public function credit($amount)
    {
        $this->balance += $amount;
        $this->save();
    }

    public function debit($amount)
    {
        if ($amount > $this->available_balance) {
            throw new \Exception('Insufficient available balance.');
        }

        $this->balance -= $amount;
        $this->save();
    }

    public function lockAmount($amount)
    {
        $this->locked_balance += $amount;
        $this->save();
    }

    public function unlockAmount($amount)
    {
        $this->locked_balance = max($this->locked_balance - $amount, 0);
        $this->save();
    }

    public function moveToPending($amount)
    {
        if ($amount > $this->available_balance) {
            throw new \Exception('Insufficient funds to move into pending.');
        }

        $this->pending_balance += $amount;
        $this->save();
    }

    public function settlePending($amount)
    {
        $this->pending_balance = max($this->pending_balance - $amount, 0);
        $this->save();
    }
}
