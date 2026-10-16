<?php

namespace App\Enums;

enum VehicleType: string
{
    case VAN            = 'van';
    case TRUCK          = 'truck';
    case BIKE           = 'bike';
    case TRAILER        = 'trailer';
    case BUS            = 'bus';
    case CAR            = 'car';
    case PICKUP         = 'pickup';
    case SUV            = 'suv';
    case THREE_WHEELER  = 'three_wheeler';
    case BOAT           = 'boat';         // Water-based delivery
    case AIRPLANE       = 'airplane';     // Air cargo
    case HELICOPTER     = 'helicopter';   // Rapid or emergency drops
    case DRONE          = 'drone';        // Lightweight or autonomous delivery

    public function label(): string
    {
        return match ($this) {
            self::VAN            => _app('van'),
            self::TRUCK          => _app('truck'),
            self::BIKE           => _app('motorbike'),
            self::TRAILER        => _app('trailer'),
            self::BUS            => _app('bus'),
            self::CAR            => _app('car'),
            self::PICKUP         => _app('pickup_truck'),
            self::SUV            => _app('suv'),
            self::THREE_WHEELER  => _app('three_wheeler'),
            self::BOAT           => _app('boat'),
            self::AIRPLANE       => _app('airplane'),
            self::HELICOPTER     => _app('helicopter'),
            self::DRONE          => _app('drone'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::VAN            => '🚐',
            self::TRUCK          => '🚛',
            self::BIKE           => '🏍️',
            self::TRAILER        => '🛻',
            self::BUS            => '🚌',
            self::CAR            => '🚗',
            self::PICKUP         => '🚙',
            self::SUV            => '🚘',
            self::THREE_WHEELER  => '🛺',
            self::BOAT           => '🚤',
            self::AIRPLANE       => '✈️',
            self::HELICOPTER     => '🚁',
            self::DRONE          => '🛸',
        };
    }

    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }

    public static function options(): array
    {
        return array_map(
            fn($type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases()
        );
    }

    public function isAerial(): bool
    {
        return in_array($this, [self::AIRPLANE, self::HELICOPTER, self::DRONE]);
    }
}
