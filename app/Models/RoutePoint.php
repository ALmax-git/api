<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoutePoint extends Model
{
    protected $fillable = [
        'route_id',
        'address_id',
        'time_interval',
        'order',
    ];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
