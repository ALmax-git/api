<?php

namespace App\Enums;

enum ClientStatus: string
{
    case ACTIVE         = 'active';
    case SUSPENDED      = 'suspended';
    case SETUP          = 'setup';
    case PAUSE          = 'pause';
    case CLOSE          = 'closed';
    case UNDERREVIEW    = 'under_review';
    case TERMINATED     = 'terminated';


    public function label(): string
    {
        return match ($this) {
            self::ACTIVE        => _app('active'),
            self::SUSPENDED     => _app('suspended'),
            self::SETUP         => _app('setup'),
            self::PAUSE         => _app('pause'),
            self::CLOSE         => _app('closed'),
            self::UNDERREVIEW   => _app('under_review'),
            self::TERMINATED    => _app('terminated'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE        => '🟢',
            self::SUSPENDED     => '⛔',
            self::SETUP         => '⚙️',
            self::PAUSE         => '⏸️',
            self::CLOSE         => '🚪',
            self::UNDERREVIEW   => '🔎',
            self::TERMINATED    => '☠️',
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
