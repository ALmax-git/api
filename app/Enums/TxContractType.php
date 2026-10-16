<?php

namespace App\Enums;

enum TxContractType: string
{
    case PURCHASE = 'purchase';          // Buyer pays, seller delivers
    case ESCROW = 'escrow';              // Held by third party until both confirm
    case PLEDGE = 'pledge';              // Like a deposit or guarantee
    case REFUND = 'refund';              // Return transaction
    case LOAN = 'loan';                  // Timed lending agreement
    case SERVICE = 'service';            // Service agreement
    case SUBSCRIPTION = 'subscription';  // Recurring delivery
    case DONATION = 'donation';          // No exchange, just tracking

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
