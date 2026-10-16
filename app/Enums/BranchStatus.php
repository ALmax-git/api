<?php

namespace App\Enums;

enum BranchStatus: string
{
    case ACTIVE         = 'active';
    case SUSPENDED      = 'suspended';
    case SETUP          = 'setup';
    case CLOSE          = 'closed';
    case UNDERREVIEW    = 'under_review';
    case TERMINATED     = 'terminated';


    public function label(): string
    {
        return match ($this) {
            self::ACTIVE        => _app('active'),
            self::SUSPENDED     => _app('suspended'),
            self::SETUP         => _app('setup'),
            self::CLOSE         => _app('closed'),
            self::UNDERREVIEW   => _app('under_review'),
            self::TERMINATED    => _app('terminated'),
        };
    }
    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE        => '🟢', // or 'fas fa-check-circle'
            self::SUSPENDED     => '⛔', // or 'fas fa-ban'
            self::SETUP         => '⚙️', // or 'fas fa-cogs'
            self::CLOSE         => '🚪', // or 'fas fa-door-closed'
            self::UNDERREVIEW   => '🔎', // or 'fas fa-search'
            self::TERMINATED    => '☠️', // or 'fas fa-skull-crossbones'
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
