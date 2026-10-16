<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'label',
        'title',
        'description',
        'status', # ['active', 'deactivated', 'archived']
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'plan_features');
    }
}
