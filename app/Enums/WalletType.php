<?php

namespace App\Enums;

enum WalletType: string
{
    case CURRENT                = 'current';
    case SAVINGS                = 'savings';
    case SYSTEM                 = 'system';

    public function label(): string
    {
        return match ($this) {
            self::CURRENT              => _app('current'),
            self::SAVINGS              => _app('savings'),
            self::SYSTEM               => _app('system'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CURRENT              => '🟢',
            self::SAVINGS              => '⚪',
            self::SYSTEM               => '🐝️',
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
