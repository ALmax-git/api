<?php

namespace App\Models;

use App\Enums\GeoStatus;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'flag',
        'iso2',
        'iso3',
        'currency',
        'status',
        'created_by',
        'update_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'        => GeoStatus::class,
        ];
    }

    public function states()
    {
        return  $this->hasMany(State::class)->where('status',  GeoStatus::ACTIVE->value);
    }
    public function scopeActive($query)
    {
        return $query->where('status', GeoStatus::ACTIVE->value);
    }
}
