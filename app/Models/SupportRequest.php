<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportRequest extends Model
{

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'message',
        'device',
        'user_agent',
        'ip',
        'longitude',
        'latitude',
        'timezone',
        'country',
        'state',
        'city',
        'status',
        'client_id',
        'is_treated'
    ];

    /**
     * Relationship with the Client model (a product belongs to a client).
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
