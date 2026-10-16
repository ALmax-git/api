<?php

namespace App\Enums;

enum ProductType: string
{
    case ITEM                           = 'Item';
    case DIGITAL                        = 'Digital';
        // case SERVICE                        = 'service';
    case LIVESTOCK                      = 'Livestock';
    case SOFTWARE                       = 'Software';

    public function label(): string
    {
        return match ($this) {
            self::ITEM                  => _app('item'),
            self::DIGITAL               => _app('Digital'),
            self::SOFTWARE               => _app('software'),
            self::LIVESTOCK             => _app('livestock'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ITEM                  => '📦️',
            self::DIGITAL               => '💾️',
            self::SOFTWARE               => '💿️',
            self::LIVESTOCK             => '🐄️',
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
