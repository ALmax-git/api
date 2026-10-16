<?php

namespace App\Models;

use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class XDeveloper extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'website',
        'support_email',
        'status',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
        'status' => UserStatus::class,
    ];

    /**
     * Link to the physical Core-X user account managing this developer profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all apps published by this developer.
     */
    public function apps(): HasMany
    {
        return $this->hasMany(XApp::class, 'developer_id');
    }
}
