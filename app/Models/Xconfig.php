<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Xconfig extends Model
{
    protected $fillable = [
        'client_id',
        'label',
        'title',
        'sub_title',
        'context',
        'is_live',
        'all_nav',
    ];


    protected $casts = [
        'is_live' => 'boolean',
        'all_nav' => 'boolean',
    ];


    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function xnavs()
    {
        return $this->belongsToMany(Xnav::class, 'xnav_xconfigs', 'xconfigs_id', 'xnavs_id')
            ->withPivot('id', 'order');
    }
}
