<?php

namespace App\Enums;

enum PlansType: string
{
    case BRONZE                         = 'Bronze';
    case GOLD                           = 'Gold';
    case DIAMOND                        = 'Diamond';


    public function label(): string
    {
        return match ($this) {
            self::BRONZE                => _app('Bronze'),
            self::GOLD                  => _app('Gold'),
            self::DIAMOND               => _app('Diamond'),
        };
    }
    public function icon(): string
    {
        return match ($this) {
            self::BRONZE                => '🟤️',
            self::GOLD                  => '🪙️',
            self::DIAMOND               => '💎️',
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
