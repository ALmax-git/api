<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Xnav extends Model
{
    protected $fillable = [
        'client_id',
        'is_live',
        'icon',
        'label',
        'tooltip',
        'slug',
        'order',
    ];


    protected $casts = [
        'is_live' => 'boolean',
        'order' => 'integer',
    ];


    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    public function xconfigs()
    {
        return $this->belongsToMany(Xconfig::class, 'xnav_xconfigs', 'xnavs_id', 'xconfigs_id')
            ->withPivot('id', 'order')
            ->orderBy('xnav_xconfigs.order');
    }
}
