<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referee extends Model
{
    protected $fillable = [
        'user_id',
        '_user_id',
        'name',
        'referee_number',
        'known_period',
        'current_account_with',
        'referee_name',
        'bank_name_address',
        'referee_account_number',
    ];


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function referee()
    {
        return $this->belongsTo(User::class, '_user_id');
    }
}
