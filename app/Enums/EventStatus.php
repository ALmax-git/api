<?php

namespace App\Enums;

enum EventStatus: string
{
    case DRAFT              = 'draft';
    case SCHEDULED          = 'scheduled';
    case PUBLISHED          = 'published';
    case CANCELLED          = 'cancelled';
    case COMPLETED          = 'completed';
    case POSTPONED          = 'postponed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Draft',
            self::SCHEDULED => 'Scheduled',
            self::PUBLISHED => 'Published',
            self::CANCELLED => 'Cancelled',
            self::COMPLETED => 'Completed',
            self::POSTPONED => 'Postponed',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT     => '📝',
            self::SCHEDULED => '📅',
            self::PUBLISHED => '🚀',
            self::CANCELLED => '❌',
            self::COMPLETED => '✅',
            self::POSTPONED => '⏳',
        };
    }

    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }


    public function is_active(): bool
    {
        return in_array($this, [self::SCHEDULED, self::PUBLISHED], true);
    }
}
