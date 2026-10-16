<?php

namespace App\Models;

use App\Enums\FacilityStatus;
use App\Enums\FacilityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facility extends Model
{
    protected $fillable = [
        'client_id',
        'branch_id',
        'address_id',
        'manager_id',
        'name',
        'type',
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
            'status'        => FacilityStatus::class,
            'type'          => FacilityType::class,
        ];
    }


    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
