<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class XApp extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'developer_id',
        'category_id',
        'name',
        'tagline',
        'description',
        'icon_path',
        'is_system',
        'requires_consent',
        'status',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'requires_consent' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        // Automatically generate a secure UUID for API routing
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // --- RELATIONSHIPS ---

    public function developer(): BelongsTo
    {
        return $this->belongsTo(XDeveloper::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(XCategory::class);
    }

    public function policies(): HasMany
    {
        return $this->hasMany(XAppPolicy::class);
    }

    /**
     * Get the currently active policy for this app.
     */
    public function activePolicy(): HasOne
    {
        return $this->hasOne(XAppPolicy::class)->where('is_active', true);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(XAppRelease::class);
    }

    /**
     * Get the current stable release.
     */
    public function currentRelease(): HasOne
    {
        return $this->hasOne(XAppRelease::class)->where('is_current', true);
    }

    public function installations(): HasMany
    {
        return $this->hasMany(XUserInstallation::class);
    }

    // --- UTILITY METHODS ---

    /**
     * Check if a specific user has installed and consented to this app.
     */
    public function isInstalledAndConsentedByUser($user): bool
    {
        if ($this->is_system && !$this->requires_consent) {
            return true; // System apps without policies are automatically valid
        }

        $installation = $this->installations()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$installation) {
            return false;
        }

        if ($this->requires_consent) {
            // Check if they consented, and if they consented to the LATEST active policy
            $activePolicy = $this->activePolicy;
            if ($activePolicy && $installation->agreed_policy_id !== $activePolicy->id) {
                return false; // They need to agree to the new updated terms!
            }
        }

        return true;
    }
}
