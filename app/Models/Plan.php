<?php

namespace App\Models;

use App\Enums\GeneralStatus;
use App\Enums\PlansType;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'label',
        'description',
        'activation_fee',
        'recurring_fee',
        'billing_cycle', #  ["monthly", "annual"]
        'status', #  ['active', 'deactivated', 'archived'] active
        'is_system_asign',
        'is_visible',
        'type'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type'          => PlansType::class,
            'status'        => GeneralStatus::class,
        ];
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'plan_features')->where('status', 'active');
    }
}
