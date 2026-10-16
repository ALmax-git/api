<?php

namespace App\Models;

use App\Enums\DepartmentTypeStatus;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepartmentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'title',
        'description',
        'user_id',
        'status' # ['active', 'deactivated', 'archived']
    ];

    protected $casts = [
        'status' => DepartmentTypeStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'type_id');
    }

    public function leave_route(): HasMany
    {
        return $this->hasMany(LeaveRoute::class, 'department_type_id')->orderBy('order');
    }

    public function taget_leave_route(): HasMany
    {
        return $this->hasMany(LeaveRoute::class, 'target_department_type_id')->orderBy('order');
    }

    public function staffs()
    {
        return User::whereHas('userClient', function ($q) {
            $q->whereIn('department_id', $this->departments()->pluck('id'))
                ->where('is_staff', true);
        });
    }

    public function units()
    {
        return DepartmentUnit::whereIn('department_id', $this->departments()->pluck('id'));
    }
}
