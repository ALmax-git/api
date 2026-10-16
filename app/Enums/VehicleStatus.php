<?php

namespace App\Enums;

enum VehicleStatus: string
{
    case AVAILABLE     = 'available';       // Ready for a new assignment
    case ON_ROUTE      = 'on_route';        // Currently delivering
    case MAINTENANCE   = 'maintenance';     // Under maintenance
    case DELAYED       = 'delayed';         // Unexpected delay
    case UNAVAILABLE   = 'unavailable';     // Inactive or grounded
    case LOADING       = 'loading';         // Being loaded
    case UNLOADING     = 'unloading';       // Arrived, offloading
    case IDLE          = 'idle';            // Assigned but waiting
    case BROKEN_DOWN   = 'broken_down';     // Needs repairs

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE     => _app('available'),
            self::ON_ROUTE      => _app('on_route'),
            self::MAINTENANCE   => _app('maintenance'),
            self::DELAYED       => _app('delayed'),
            self::UNAVAILABLE   => _app('unavailable'),
            self::LOADING       => _app('loading'),
            self::UNLOADING     => _app('unloading'),
            self::IDLE          => _app('idle'),
            self::BROKEN_DOWN   => _app('broken_down'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AVAILABLE     => '✅',
            self::ON_ROUTE      => '🚚',
            self::MAINTENANCE   => '🔧',
            self::DELAYED       => '⏳',
            self::UNAVAILABLE   => '❌',
            self::LOADING       => '📦',
            self::UNLOADING     => '📤',
            self::IDLE          => '🕓',
            self::BROKEN_DOWN   => '⚠️',
        };
    }

    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }

    public static function options(): array
    {
        return array_map(
            fn($status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }
}
