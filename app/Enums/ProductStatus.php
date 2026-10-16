<?php

namespace App\Enums;

enum ProductStatus: string
{
    case VERIFIED           = 'verified';
    case NEW                = 'new';
    case ACTIVE             = 'active';
    case INACTIVE           = 'inactive';
    case OUTOFSTOCK         = 'out_of_stock';
    case DISCONTINUED       = 'discontinued';
    case ARCHIVED           = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::VERIFIED          => _app('verified'),
            self::NEW               => _app('new'),
            self::INACTIVE          => _app('in_active'),
            self::ACTIVE            => _app('active'),
            self::OUTOFSTOCK        => _app('out_of_stock'),
            self::DISCONTINUED      => _app('discontinued'),
            self::ARCHIVED          => _app('archived'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::VERIFIED       => '✅',
            self::NEW            => '🆕',
            self::ACTIVE         => '🟢',
            self::INACTIVE       => '⚪',
            self::OUTOFSTOCK     => '❌',
            self::DISCONTINUED   => '📴',
            self::ARCHIVED       => '📦',
        };
    }

    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }

    public static function options(): array
    {
        return array_map(
            fn($type) => [
                'value'             => $type->value,
                'label'             => $type->label()
            ],
            self::cases()
        );
    }
}
