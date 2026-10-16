<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientGeoRestriction extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'country_id',
        'state_id',
        'city_id',
    ];


    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function country(): BelongsTo
    {
        return   $this->belongsTo(Country::class, 'country_id');
    }

    public function state(): BelongsTo
    {
        return  $this->belongsTo(State::class);
    }
}
