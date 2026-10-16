<?php

namespace App\Enums;

enum WalletTier: string
{
    case TIER_ONE                = 'tier_one';
    case TIER_TWO                = 'tier_two';
    case TIER_THREE              = 'tier_three';

    public function label(): string
    {
        return match ($this) {
            self::TIER_ONE              => _app('current'),
            self::TIER_TWO              => _app('savings'),
            self::TIER_THREE            => _app('tier_three'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TIER_ONE              => '🐝️',
            self::TIER_TWO              => '🐝️🐝️',
            self::TIER_THREE            => '🐝️🐝️🐝️',
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
