<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosCart extends Model
{

    protected $fillable = [
        'client_id',
        'user_id',
        'items',
        'customer_name',
        'customer_phone',
        'customer_email',
    ];

    protected $casts = [
        'items' => 'array', // Cast items to an array
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
