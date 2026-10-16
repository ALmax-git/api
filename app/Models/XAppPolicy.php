<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XAppPolicy extends Model
{
    protected $fillable = [
        'x_app_id',
        'version',
        'terms_of_service',
        'privacy_policy',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(XApp::class, 'x_app_id');
    }

    /**
     * Marks this policy as active and deactivates all older policies for the app.
     */
    public function activate(): void
    {
        // Deactivate others
        static::where('x_app_id', $this->x_app_id)->update(['is_active' => false]);

        // Activate this one
        $this->update([
            'is_active' => true,
            'published_at' => now(),
        ]);
    }
}
