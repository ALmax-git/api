<?php

namespace App\Models;

use App\Enums\TxContractType;
use App\Enums\TxContractStatus;
use App\Enums\TxContractFinalAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TxContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'tx_ref',
        '_tx_ref',
        'model',
        'model_id',
        'wallet_id',
        '_wallet_id',
        'asset_id',
        'initiator_id',
        'counterparty_id',
        'hash',
        'amount',
        'description',
        'contract_type',
        'status',
        'deal_end_at',
        'on_deal_auto_end',
        'final_action',
        'requires_verification',
        'conditions',
        'verified',
        'events_log',
        'system_signature',
    ];

    protected $casts = [
        'contract_type' => TxContractType::class,
        'status' => TxContractStatus::class,
        'final_action' => TxContractFinalAction::class,
        'deal_end_at' => 'datetime',
        'conditions' => 'array',
        'events_log' => 'array',
        'requires_verification' => 'boolean',
        'verified' => 'boolean',
    ];

    // 🔗 Relationships

    public function tx()
    {
        return $this->belongsTo(Transaction::class, 'tx_ref');
    }

    public function _tx()
    {
        return $this->belongsTo(Transaction::class, '_tx_ref');
    }

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

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function counterparty()
    {
        return $this->belongsTo(User::class, 'counterparty_id');
    }

    // 🧠 Logic Methods

    public function is_expired(): bool
    {
        return now()->greaterThan($this->deal_end_at);
    }

    public function should_forward(): bool
    {
        return $this->on_deal_auto_end === 'forward';
    }

    public function should_rollback(): bool
    {
        return $this->on_deal_auto_end === 'rollback';
    }

    public function is_pending(): bool
    {
        return $this->status === TxContractStatus::PENDING;
    }

    public function mark_as_verified()
    {
        $this->update([
            'verified' => true,
            'status' => TxContractStatus::SUCCESS->value,
        ]);
    }

    public function log_event(string $event)
    {
        $log = $this->events_log ?? [];
        $log[] = ['timestamp' => now()->toDateTimeString(), 'event' => ' ' . $event];
        $this->update(['events_log' => $log]);
    }
}
