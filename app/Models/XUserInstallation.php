<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XUserInstallation extends Model
{
    protected $fillable = [
        'user_id',
        'x_app_id',
        'agreed_policy_id',
        'consented_at',
        'consent_ip_address',
        'is_active',
        'installed_at',
        'uninstalled_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'consented_at' => 'datetime',
        'installed_at' => 'datetime',
        'uninstalled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(XApp::class, 'x_app_id');
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(XAppPolicy::class, 'agreed_policy_id');
    }

    /**
     * Record a user's consent for an app policy.
     */
    public function recordConsent(XAppPolicy $policy, string $ipAddress = null): void
    {
        $this->update([
            'agreed_policy_id' => $policy->id,
            'consented_at' => now(),
            'consent_ip_address' => $ipAddress,
        ]);
    }

    /**
     * Uninstall the app for the user.
     */
    public function uninstall(): void
    {
        $this->update([
            'is_active' => false,
            'uninstalled_at' => now(),
        ]);
    }
}
