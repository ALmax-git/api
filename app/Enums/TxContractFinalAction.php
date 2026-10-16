<?php

namespace App\Enums;

enum TxContractFinalAction: string
{
    case PENDING = 'pending';        // No action yet
    case FORWARDED = 'forwarded';    // Funds/delivery moved forward
    case ROLLED_BACK = 'rolled_back'; // Reversed or refunded
    case MANUAL = 'manual';          // Admin manually resolved

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
