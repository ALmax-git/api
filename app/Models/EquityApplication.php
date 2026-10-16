<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquityApplication extends Model
{
    protected $fillable = [
        'user_id',
        'pfa_id',
        'pen',
        'morgage_type',
        'morgage_lender_address',
        'morgage_lender',
        'property_address',
        'property_type',
        'property_value',
        'rsa_balance',
        'rsa_holder',
        'equity_amount',
        'supplementary_payment_amount',
        'sworn_date',
        'check_list',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function pfa(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'pfa_id');
    }
}
