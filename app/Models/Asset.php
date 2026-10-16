<?php

namespace App\Models;

use App\Enums\AssetsType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'assets';

    protected $fillable = [
        'type',
        'icon_url',
        'label',
        'symbol',
        'is_verified',
        'value',
        'code',
        'metadata',
        'description',
        'user_id',
        'is_active',
        'source',
        'verified_at',
        'withdrawal_fee',
        'network_fee',
    ];

    protected $casts = [
        'metadata'       => 'array',
        'is_verified'    => 'boolean',
        'is_active'      => 'boolean',
        'verified_at'    => 'datetime',
        'withdrawal_fee' => 'decimal:8',
        'network_fee'    => 'decimal:8',
        'value'          => 'float',
        'type'           => AssetsType::class,
    ];

    /**
     * RELATIONSHIPS
     */

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    /**
     * ACCESSORS
     */

    public function getAvailableBalanceAttribute()
    {
        $balance  = 0;
        $balance += asset_value($this, $this->balance);
        return max($balance, 0);
    }
    /**
     * SCOPES
     */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * HELPERS
     */

    public function verify()
    {
        $this->is_verified = true;
        $this->verified_at = now();
        $this->save();
    }

    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }
}
