<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{

    protected $fillable = [
        'client_id',
        'driver_id',
        'name',
        'model',
        'plate_number',
        'type',
        'capacity',
        'max_weight',
        'max_volume',
        'cost_modifier',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status'        => VehicleStatus::class,
            'type'          => VehicleType::class,
            'max_weight'    => 'integer',
            'max_volume'    => 'integer',
            'cost_modifier' => 'float',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
