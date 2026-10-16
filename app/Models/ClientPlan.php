<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPlan extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plan_id',
        'user_id',
        'client_id',
        'activated_at',
        'expired_at',
        'status', # ["active", "cancelled", "expired", 'pending'] pending
        'count',
        'recent_tx_ref'
    ];
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
