<?php

namespace App\Enums;

enum RouteStatus: string
{
    case DRAFT        = 'draft';        // Created but not active
    case ACTIVE       = 'active';       // Available for assignment
    case IN_USE       = 'in_use';       // Being used in transit
    case COMPLETED    = 'completed';    // Route finished
    case SUSPENDED    = 'suspended';    // Temporarily paused
    case CANCELLED    = 'cancelled';    // Discarded/removed

    public function label(): string
    {
        return match ($this) {
            self::DRAFT      => _app('draft'),
            self::ACTIVE     => _app('active'),
            self::IN_USE     => _app('in_use'),
            self::COMPLETED  => _app('completed'),
            self::SUSPENDED  => _app('suspended'),
            self::CANCELLED  => _app('cancelled'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT      => '📝',
            self::ACTIVE     => '✅',
            self::IN_USE     => '🚚',
            self::COMPLETED  => '🏁',
            self::SUSPENDED  => '⏸️',
            self::CANCELLED  => '❌',
        };
    }

    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }
}
