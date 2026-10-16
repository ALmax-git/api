<?php

namespace App\Enums;

enum JournalEntryType: string
{
    case POSITIVE                   = 'positive';
    case NEGATIVE                   = 'negative';
    // case MANUAL                     = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::POSITIVE          => _app('positive'),
            self::NEGATIVE          => _app('negative'),
            // self::MANUAL            => _app('manual'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::POSITIVE          => '⬇️',
            self::NEGATIVE          => '⬆️',
            // self::MANUAL            => '🔄',
        };
    }

    public function svg(): string
    {
        return match ($this) {
            self::POSITIVE          => 'M12 6v6m0 0v6m0-6h6m-6 0H6',
            self::NEGATIVE          => 'M11 14v7l9-11h-7V3L4 14h7Z',
            // self::MANUAL            => 'M6 3v5h5M18 21v-5h-5M6 8l7 7m0 0l7-7m-7 7V3m0 16v-6',
        };
    }

    public function bg_color(): string
    {
        return match ($this) {
            self::POSITIVE          => 'bg-green-100',
            self::NEGATIVE          => 'bg-red-100',
            // self::MANUAL            => 'bg-blue-100',
        };
    }

    public function shadow_color(): string
    {
        return match ($this) {
            self::POSITIVE          => 'shadow-green-200/50',
            self::NEGATIVE          => 'shadow-red-200/50',
            // self::MANUAL            => 'shadow-blue-200/50',
        };
    }
    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }
}
