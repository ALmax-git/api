<?php

namespace App\Models;

use App\Enums\GeoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'postal_code',
        'status',
        'state_id',
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

    public function country(): BelongsTo
    {
        return   $this->belongsTo(Country::class, 'country_id');
    }

    public function state(): BelongsTo
    {
        return  $this->belongsTo(State::class);
    }

    public function creator(): BelongsTo
    {
        return  $this->belongsTo(User::class, 'created_by');
    }

    public function updates_by(): BelongsTo
    {
        return  $this->belongsTo(State::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', GeoStatus::ACTIVE->value);
    }
}
