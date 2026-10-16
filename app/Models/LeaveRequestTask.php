<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequestTask extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'staff_id',
        'user_leave_request_id',
        'order',
        'status', # ['pending', 'fowarded', 'reverted', 'completed']
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
    public function user_leave_request()
    {
        return $this->belongsTo(UserLeaveRequest::class, 'user_leave_request_id');
    }
}
