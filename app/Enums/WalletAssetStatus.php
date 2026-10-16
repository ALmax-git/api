<?php

namespace App\Enums;

enum WalletAssetStatus: string
{
    case ACTIVE                 = 'active';
    case FROZEN                 = 'frozen';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE                => _app('active'),
            self::FROZEN                => _app('frozen'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE                => '🟢',
            self::FROZEN                => '❄️',
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
