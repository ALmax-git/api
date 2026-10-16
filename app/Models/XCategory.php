<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class XCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all apps within this category.
     */
    public function apps(): HasMany
    {
        return $this->hasMany(XApp::class, 'category_id');
    }
}
