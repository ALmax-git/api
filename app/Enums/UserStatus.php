<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE                 = 'active';                 // Full access
    case INACTIVE               = 'inactive';               // Account created but not yet active
    case SUSPENDED              = 'suspended';              // Temporarily blocked (fraud, abuse, etc.)
    case BANNED                 = 'banned';                 // Permanently blocked
    case PENDING_VERIFICATION   = 'pending_verification';   // Email/SMS or KYC verification required
    case REJECTED               = 'rejected';               // KYC or access denial
    case DEACTIVATED            = 'deactivated';            // User chose to deactivate
    case UNDER_REVIEW           = 'under_review';           // Account flagged for manual review
    case ARCHIVED               = 'archived';               // Soft-deleted or historical record

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE                => 'Active',
            self::INACTIVE              => 'Inactive',
            self::SUSPENDED             => 'Suspended',
            self::BANNED                => 'Banned',
            self::PENDING_VERIFICATION  => 'Pending Verification',
            self::REJECTED              => 'Rejected',
            self::DEACTIVATED           => 'Deactivated',
            self::UNDER_REVIEW          => 'Under Review',
            self::ARCHIVED              => 'Archived',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE                => '🟢',
            self::INACTIVE              => '⚪',
            self::SUSPENDED             => '⛔',
            self::BANNED                => '🚫',
            self::PENDING_VERIFICATION  => '⏳',
            self::REJECTED              => '❌',
            self::DEACTIVATED           => '🔌',
            self::UNDER_REVIEW          => '🔍',
            self::ARCHIVED              => '📁',
        };
    }

    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::ACTIVE               => 'green',
            self::INACTIVE            => 'gray',
            self::SUSPENDED           => 'yellow',
            self::BANNED              => 'red',
            self::PENDING_VERIFICATION => 'blue',
            self::REJECTED            => 'red',
            self::DEACTIVATED         => 'gray',
            self::UNDER_REVIEW        => 'orange',
            self::ARCHIVED            => 'indigo',
        };
    }
    public static function options(): array
    {
        return array_map(
            fn($status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }
}
