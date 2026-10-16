<?php

namespace App\Models;

use App\Enums\EmpowermentStatus;
use Illuminate\Database\Eloquent\Model;

class Empowerment extends Model
{

    protected $fillable = [
        'user_id',
        'target_id',
        'client_id',
        'title',
        'white_paper',
        'status'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'        => EmpowermentStatus::class,
        ];
    }

    public function hr()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function target()
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}
