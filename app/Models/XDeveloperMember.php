<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XDeveloperMember extends Model
{
    protected $fillable = [
        'x_developer_id',
        'user_id',
        'role',
        'is_active',
        'joined_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'joined_at' => 'datetime',
    ];

    /**
     * Get the developer team this membership belongs to.
     */
    public function developerTeam(): BelongsTo
    {
        return $this->belongsTo(XDeveloper::class, 'x_developer_id');
    }

    /**
     * Get the Core-X user who holds this membership.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to check permissions easily
     */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles);
    }
}
