<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRoute extends Model
{
    protected $fillable = [
        'department_type_id',
        'client_id',
        'user_id', # nullable | if target staff
        'target_department_type_id', # nullable | if target department_type
        'role_id', # nullable | role/staff role
        'target', # ['staff', 'head_of_unit', 'head_of_department', 'department_type', 'role']
        'order', # route direction
        'office_title', # nullable
        'can_publish',
        'can_approve',
        'can_comment',
        'can_reject',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    public function department_type(): BelongsTo
    {
        return $this->belongsTo(DepartmentType::class, 'department_type_id');
    }
    public function target_department_type(): BelongsTo
    {
        return $this->belongsTo(DepartmentType::class, 'target_department_type_id');
    }
}
