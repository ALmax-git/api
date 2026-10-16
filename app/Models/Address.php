<?php

namespace App\Models;

use App\Enums\AddressType;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'type',
        'label',
        'street',
        'address_line_1',
        'address_line_2',
        'phone_number',
        'whatsapp_contact',
        'email',
        'landmark',
        'area_code',
        'postal_code',
        'latitude',
        'longitude',
        'altitude',
        'country_id',
        'state_id',
        'city_id',
        'is_default'
    ];

    protected $casts = [
        'type' => AddressType::class,
    ];

    public function full_address(): string
    {
        return $this->country . ' ' . $this->state . ' ' . $this->city . ' ' . $this->address_line_1 . ' Tel: ' . $this->phone_number . ' Email: ' . $this->email;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
