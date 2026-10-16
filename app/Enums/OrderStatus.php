<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING                    = 'pending';                // Order created but not yet paid
    case PAID                       = 'paid';                   // Payment received
    case CONFIRMED                  = 'confirmed';              // Confirmed by vendor
    case PACKAGING                  = 'packaging';              // Packaging by DriftNet
    case PROCESSING                 = 'processing';             // Being prepared
    case SHIPPED                    = 'shipped';                // Left the warehouse or vendor
    case IN_TRANSIT                 = 'in_transit';             // On the way to customer
    case OUT_FOR_DELIVERY           = 'out_for_delivery';       // Near final destination
    case DELIVERED                  = 'delivered';              // Successfully delivered
    case CANCELLED                  = 'cancelled';              // Cancelled by user or vendor
    case FAILED                     = 'failed';                 // Failed payment or processing
    case RETURNED                   = 'returned';               // Customer returned item
    case REFUNDED                   = 'refunded';               // Refund issued

    public function label(): string
    {
        return match ($this) {
            self::PENDING           => 'Pending',
            self::PAID              => 'Paid',
            self::CONFIRMED         => 'Confirmed',
            self::PACKAGING         => 'Packaging',
            self::PROCESSING        => 'Processing',
            self::SHIPPED           => 'Shipped',
            self::IN_TRANSIT        => 'In Transit',
            self::OUT_FOR_DELIVERY  => 'Out for Delivery',
            self::DELIVERED         => 'Delivered',
            self::CANCELLED         => 'Cancelled',
            self::FAILED            => 'Failed',
            self::RETURNED          => 'Returned',
            self::REFUNDED          => 'Refunded',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING           => '⏳',
            self::PAID              => '💳',
            self::CONFIRMED         => '✅',
            self::PROCESSING        => '⚙️',
            self::SHIPPED           => '📦',
            self::PACKAGING         => '📦',
            self::IN_TRANSIT        => '🚚',
            self::OUT_FOR_DELIVERY  => '📍',
            self::DELIVERED         => '📬',
            self::CANCELLED         => '❌',
            self::FAILED            => '💥',
            self::RETURNED          => '↩️',
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
