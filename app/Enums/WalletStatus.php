<?php

namespace App\Enums;

enum WalletStatus: string
{
    case ACTIVE                 = 'active';
    case LOCKED                 = 'locked';
    case PENDING_VERIFICATION   = 'pending_verification';
    case UNDER_REVIEW           = 'under_review';
    case REJECTED               = 'rejected';
    case DEACTIVATED            = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE                => _app('active'),
            self::LOCKED                => _app('locked'),
            self::PENDING_VERIFICATION  => _app('pending_verification'),
            self::UNDER_REVIEW          => _app('under_review'),
            self::REJECTED              => _app('rejected'),
            self::DEACTIVATED           => _app('deactivated'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE                => '🟢',
            self::LOCKED                => '🔐️',
            self::PENDING_VERIFICATION  => '⏳',
            self::UNDER_REVIEW          => '🔍',
            self::REJECTED              => '❌',
            self::DEACTIVATED           => '🔌',
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
