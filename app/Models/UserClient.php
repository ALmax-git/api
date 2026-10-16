<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserClient extends Model
{
    protected $fillable = [
        'user_id',
        'is_staff',
        'client_id',
        'level_id',
        'staff_number',
        'department_id',
        'department_unit_id',
        'enroll_by_id',
        'is_beneficiary',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
    public function department_unit(): BelongsTo
    {
        return $this->belongsTo(DepartmentUnit::class, 'department_unit_id');
    }
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function enroll_by(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enroll_by_id');
    }
}
