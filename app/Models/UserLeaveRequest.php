<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLeaveRequest extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'user_id',
        'leave_id',
        'description',
        'has_attachment',
        'starting_day',
        'resuming_day',
        'status', #  ['pending', 'approved', 'rejected', 'archived', 'reverted']
        'route_id',
        'address_id'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'has_attachment' => 'boolean',
            'starting_day' => 'date',
            'resuming_day' => 'date',
            'status' => 'string',
        ];
    }
    public function route()
    {
        return $this->belongsTo(LeaveRoute::class, 'route_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }
    public function leave()
    {
        return $this->belongsTo(Leave::class, 'leave_id');
    }
}
