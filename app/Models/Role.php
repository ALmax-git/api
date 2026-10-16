<?php

namespace App\Models;

use App\Enums\PermissionStatus;
use App\Enums\RoleStatus;
use App\Livewire\Auth\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth as FacadesAuth;

class Role extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
        'user_id',
        'client_id',
        'is_universal',
    ];

    protected $casts = [
        'status'        => RoleStatus::class,
        'is_universal' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id')->where('permissions.status', PermissionStatus::ACTIVE->value);
    }

    public function leave_route(): HasMany
    {
        return $this->hasMany(LeaveRoute::class, 'role_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->where('user_roles.client_id', FacadesAuth::user()->client->id);
    }
}
