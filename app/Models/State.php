<?php

namespace App\Models;

use App\Enums\GeoStatus;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'status',
        'country_id',
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

    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function cities()
    {
        return $this->hasMany(City::class)->where('status', GeoStatus::ACTIVE->value);
    }

    public function scopeActive($query)
    {
        return $query->where('status', GeoStatus::ACTIVE->value);
    }
}
