<?php

namespace App\Models;

use App\Enums\PermissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'label',
        'title',
        'description',
        'is_system',
        'status',
    ];

    protected $casts = [
        'status'        => PermissionStatus::class,
        'is_system'     => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id');
    }
}
