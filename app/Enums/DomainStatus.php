<?php

namespace App\Enums;

enum DomainStatus: string
{
    case ACTIVE         = 'active';
    case SETUP          = 'setup';
    case CLOSE          = 'closed';
        // case BARNE          = 'closed';
    case MAINTENANCE    = 'maintenance';


    public function label(): string
    {
        return match ($this) {
            self::ACTIVE        => _app('active'),
            self::SETUP         => _app('setup'),
            self::CLOSE         => _app('closed'),
            self::MAINTENANCE   => _app('maintenance'),
        };
    }
    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE        => '🟢', // or 'fas fa-check-circle'
            self::SETUP         => '⚙️', // or 'fas fa-cogs'
            self::CLOSE         => '🚪', // or 'fas fa-door-closed'
            self::MAINTENANCE   => '🔧',
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
}
