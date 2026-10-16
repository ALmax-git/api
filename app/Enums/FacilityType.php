<?php

namespace App\Enums;

enum FacilityType: string
{
    case STORE          = 'store';
    case OTHER          = 'other';

    public function label(): string
    {
        return match ($this) {
            self::STORE         => _app('store'),
            self::OTHER          => _app('other'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::STORE          => '🧰',
            self::OTHER          => '❓',
        };
    }
    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }
}
