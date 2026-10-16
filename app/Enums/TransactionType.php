<?php

namespace App\Enums;

enum TransactionType: string
{
    case DEPOSIT        = 'deposit';
    case WITHDRAWAL     = 'withdrawal';
    case TRANSFER       = 'transfer';
    case SWAP           = 'swap';
    case FEE            = 'fee';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT       => _app('deposit'),
            self::WITHDRAWAL    => _app('withdrawal'),
            self::TRANSFER      => _app('transfer'),
            self::SWAP          => _app('swap'),
            self::FEE           => _app('fee'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DEPOSIT       => '➕',
            self::WITHDRAWAL    => '🧾',
            self::TRANSFER      => '↗️',
            self::SWAP          => '💱',
            self::FEE           => '💸',
        };
    }

    public function svg(): string
    {
        return match ($this) {
            self::DEPOSIT       => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
            self::WITHDRAWAL    => 'M11 14v7l9-11h-7V3L4 14h7Z',
            self::TRANSFER      => 'M4 4h16v2H4V4Zm0 7h16v2H4v-2Zm0 7h16v2H4v-2Z',
            self::SWAP          => 'M6 3v5h5M18 21v-5h-5M6 8l7 7m0 0l7-7m-7 7V3m0 16v-6',
            self::FEE           => 'M12 1C5.925 1 1 5.925 1 12s4.925 11 11 11 11-4.925 11-11S18.075 1 12 1Zm0 20c-4.963 0-9-4.037-9-9s4.037-9 9-9 9 4.037 9 9-4.037 9-9 9Zm.75-13h-1.5v1.5h1.5V8Zm0 3h-1.5v6h1.5v-6Z',
        };
    }

    public function bg_color(): string
    {
        return match ($this) {
            self::DEPOSIT       => 'hove:bg-green-600',
            self::WITHDRAWAL    => 'hove:bg-red-600',
            self::TRANSFER      => 'hove:bg-blue-600',
            self::SWAP          => 'hove:bg-yellow-600',
            self::FEE           => 'hove:bg-purple-600',
        };
    }

    public function shadow_color(): string
    {
        return match ($this) {
            self::DEPOSIT       => 'shadow-green-600/50',
            self::WITHDRAWAL    => 'shadow-red-600/50',
            self::TRANSFER      => 'shadow-blue-600/50',
            self::SWAP          => 'shadow-yellow-600/50',
            self::FEE           => 'shadow-purple-600/50',
        };
    }
    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }
}
