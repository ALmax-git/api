<?php

namespace App\Models;

use App\Enums\DepartmentStatus;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'type_id',
        'title',
        'description',
        'status',
    ];

    protected $casts = [
        'status' => DepartmentStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DepartmentType::class, 'type_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(DepartmentUnit::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function staffs(): HasMany
    {
        return $this->hasMany(UserClient::class, 'department_id')
            ->with('user')
            ->where('is_staff', true);
    }
}
