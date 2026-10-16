<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchLevel extends Model
{
    protected $fillable = [
        'client_id',
        'created_by',
        'updated_by',
        'title',
        'order',
        'can_have_children',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
}
