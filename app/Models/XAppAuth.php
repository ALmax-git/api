<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class XAppAuth extends Model
{
    protected $table = 'x_app_auths';

    protected $fillable = [
        'user_id',
        'x_app_id',
        'status',
        'token',
        'last_seen',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function xApp(): BelongsTo
    {
        return $this->belongsTo(XApp::class, 'x_app_id');
    }

    /**
     * Update last_seen to now
     */
    public function touchLastSeen(): self
    {
        $this->last_seen = Carbon::now();
        $this->save();
        return $this;
    }
}
