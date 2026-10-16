<?php

namespace App\Enums;

enum BotStatus: string
{
    case ACTIVE         = 'active';
    case INACTIVE       = 'inactive';
    case SLEEP          = 'sleep';
    case MAINTENANCE    = 'maintenance';
    case OFFLINE        = 'offline';
    case PAIRING        = 'pairing';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE            => 'Active',
            self::INACTIVE          => 'Inactive',
            self::SLEEP             => 'Sleep',
            self::MAINTENANCE       => 'Maintenance',
            self::OFFLINE           => 'Offline',
            self::PAIRING           => 'Pairing',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn($status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }
}
