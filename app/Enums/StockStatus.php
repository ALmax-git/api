<?php

namespace App\Enums;

enum StockStatus: string
{
    case PENDING        = 'pending';                 // Order created but not yet paid
    case CONFIRMED        = 'confirmed';             // Confirmed by vendor
    case CANCELLED      = 'cancelled';             // Cancelled by user or vendor
    case REJECTED       = 'rejected';               // Refund issued

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'confirmed',
            self::CANCELLED => 'Cancelled',
            self::REJECTED => 'rejected',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING           => '⏳',
            self::CONFIRMED           => '✅',
            self::CANCELLED         => '❌',
            self::REJECTED          => '💸',
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
