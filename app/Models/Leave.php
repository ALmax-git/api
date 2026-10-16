<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Leave extends Model
{

    protected $fillable = [
        'client_id',
        'title',
        'description',
        'status', # ['active', 'deactivated', 'archived']
        'frequency',
        'number_of_days',
        'has_level_rules',
        // 'allowance', # ['full', 'half', 'zero', 'custom']
        'max_allowance_count',
        'require_roster',
        'host_leave_id',
        'level_range',
        'interval',
    ];
    public function level_rule($id)
    {
        return LeaveLevelRule::where('leave_id', $this->id)->where('level_id', $id)->first();
    }
    public function levels()
    {
        return $this->hasManyThrough(
            Level::class,
            LeaveLevelRule::class,
            'leave_id', // Foreign key on LeaveLevel table
            'id', // Foreign key on Level table
            'id', // Local key on Leave table
            'level_id' // Local key on LeaveLevel table
        );
    }


    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(Leave::class, 'host_leave_id');
    }
    public function client_users(): HasMany
    {
        return $this->hasMany(UserClient::class);
    }
}
