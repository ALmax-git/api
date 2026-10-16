<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNextOfKin extends Model
{
    protected $fillable = [
        'user_id',
        'kin_id',
        'relationship',
        'is_active',
    ];
}
