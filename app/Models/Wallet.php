<?php

namespace App\Models;

use App\Enums\TransactionType;
use App\Enums\WalletStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory;

    protected $table = 'wallets';

    protected $fillable = [
        'uuid',
        'user_id',
        'client_id',
        'label',
        'icon_url',
        'address',
        'number',
        'locked_balance',
        'balance',
        'private_key_encrypted',
        'created_by',
        'provider',
        'tag',
        'metadata',
        'is_default',
        'is_active',
        'is_cold_storage',
        'status',
        'last_balance_sync',
        'activated_at',
        'deactivated_at',
        'pin',
        'account_number',
        'account_name',
        'bank_id',


    ];

    protected $casts = [
        'metadata'          => 'array',
        'locked_balance'    => 'decimal:8',
        'balance_total_usd' => 'decimal:18',
        'balance'           => 'float',
        'is_default'        => 'boolean',
        'is_active'         => 'boolean',
        'is_cold_storage'   => 'boolean',
        'last_balance_sync' => 'datetime',
        'activated_at'      => 'datetime',
        'deactivated_at'    => 'datetime',
        'status'            => WalletStatus::class,
    ];

    // AUTO-GENERATE UUID WHEN CREATING
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($wallet) {
            if (!$wallet->uuid) {
                $wallet->uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * RELATIONSHIPS
     */

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * SCOPES
     */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCold($query)
    {
        return $query->where('is_cold_storage', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function assets()
    {
        return $this->hasMany(WalletAsset::class)
            ->orderBy('created_at', 'desc')
        ;
    }
    /**
     * ACCESSORS
     */

    public function getAvailableBalanceAttribute()
    {
        $balance  = 0.0;
        foreach ($this->assets as $key => $asset) {
            $balance += asset_value($asset->asset, $asset->balance);
        }
        return (float) $balance;
    }

    /**
     * HELPERS
     */

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
    public function transactions()
    {
        return $this->hasMany(Transaction::class)
            // ->orWhere('_wallet_id', $this->id)
            // ->when($query->type->value === TransactionType::DEPOSIT->value,    fn($query) => $query->where('_wallet_id', $this->id))
            // ->where(function ($query) {
            //     if (!($query->type == TransactionType::DEPOSIT->value)) {
            //         $query->orWhere('_wallet_id', $this->id);
            //     }
            // })

            ->orWhere(function ($query) {
                $query->orWhere('_wallet_id', $this->id)->whereNot('type', TransactionType::DEPOSIT->value);
            })
            // ->whereHas('transactions',    fn($q) => $q->where('use_almax_gateways', true)->orWhere('client_id', get_id(_get_host()['uuid'], 'client')))
            ->orderBy('created_at', 'desc')
            // ->chunk(100)
        ;
    }

    public function history()
    {
        return $this->hasMany(TransactionHistory::class)
            ->orderBy('created_at', 'desc')
            ->limit(20)
        ;
    }
}
