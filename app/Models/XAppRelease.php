<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XAppRelease extends Model
{
    protected $fillable = [
        'x_app_id',
        'version_number',
        'release_notes',
        'package_path',
        'min_core_os_version',
        'status',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(XApp::class, 'x_app_id');
    }
}
