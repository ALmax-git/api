<?php

namespace App\Enums;

enum TxContractStatus: string
{
    case PENDING = 'pending';                 // Order created but not yet paid
    case SUCCESS = 'success';             // Confirmed by vendor
    case CANCELLED = 'cancelled';             // Cancelled by user or vendor
    case FAILED = 'failed';                   // Failed payment or processing
    case REFUNDED = 'refunded';               // Refund issued

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SUCCESS => 'success',
            self::CANCELLED => 'Cancelled',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING           => '⏳',
            self::SUCCESS           => '✅',
            self::CANCELLED         => '❌',
            self::FAILED            => '💥',
            self::REFUNDED          => '💸',
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
