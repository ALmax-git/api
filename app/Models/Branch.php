<?php

namespace App\Models;

use App\Enums\BranchStatus;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'client_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'manager_id',
        'branch_level_id',
        'address_id',
        'parent_id',
        'name',
        'code',
        'email',
        'phone_number',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'status' => BranchStatus::class,
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
    public function level()
    {
        return $this->belongsTo(BranchLevel::class, 'branch_level_id');
    }
    public function address()
    {
        return $this->belongsTo(Address::class);
    }
    public function parent()
    {
        return $this->belongsTo(Branch::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(Branch::class, 'parent_id');
    }
}
