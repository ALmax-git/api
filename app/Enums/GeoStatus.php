<?php

namespace App\Enums;

enum GeoStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case SUSPENDED = 'suspended';
    case BANNED = 'banned';
    case PENDING_VERIFICATION = 'pending_verification';
    case REJECTED = 'rejected';
    case DEACTIVATED = 'deactivated';
    case UNDER_REVIEW = 'under_review';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::SUSPENDED => 'Suspended',
            self::BANNED => 'Banned',
            self::PENDING_VERIFICATION => 'Pending Verification',
            self::REJECTED => 'Rejected',
            self::DEACTIVATED => 'Deactivated',
            self::UNDER_REVIEW => 'Under Review',
            self::ARCHIVED => 'Archived',
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
            self::ARCHIVED              => '📦',
        };
    }

    // Optional combo display
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
