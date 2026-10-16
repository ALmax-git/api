<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'title',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
    public function staffs()
    {
        return $this->hasMany(UserClient::class, 'department_unit_id')
            ->with('user')
            ->where('is_staff', true);
    }
}
