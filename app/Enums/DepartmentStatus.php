<?php

namespace App\Enums;

enum DepartmentStatus: string
{
    case ACTIVE                 = 'active';
    case DEACTIVATED            = 'deactivated';
    case ARCHIVED               = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE                => 'Active',
            self::DEACTIVATED           => 'Deactivated',
            self::ARCHIVED              => 'Archived',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE                => '🟢',
            self::DEACTIVATED           => '🔌',
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
            self::DEACTIVATED         => 'gray',
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
