<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Route extends Model
{
    protected $fillable = [
        'client_id',
        'branch_id',
        'address_id',
        'label',
        'friday',
        'saturday',
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thusday',
        'daily_start_time',
        'is_two_way'
    ];


    protected $casts = [
        'daily_start_time'  => 'date',
        'friday'            => 'boolean',
        'saturday'          => 'boolean',
        'sunday'            => 'boolean',
        'monday'            => 'boolean',
        'tuesday'           => 'boolean',
        'wednesday'         => 'boolean',
        'thusday'           => 'boolean',
        'is_two_way'        => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }
    public function route_points(): HasMany
    {
        return $this->hasMany(RoutePoint::class);
    }
}
