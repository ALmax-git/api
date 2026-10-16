<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $fillable = [
        'name',
        'description',
        'user_id',
        'client_id',
        'allow_almax_nodes',
        'allow_guest',
        'status',
        'welcome_page',
        "ANDROID_SHA256",
        "name_space",
        "package_name",
        "url",
        "ssl_status",
        "is_live",
        "last_checked_at",
    ];

    protected $casts = [
        'allow_almax_nodes' => 'boolean',
        'status'           => \App\Enums\DomainStatus::class,
        'is_live'          => 'boolean',
    ];

    public function getIsAdminAttribute()
    {
        return auth()->id() === 1;
    }
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
