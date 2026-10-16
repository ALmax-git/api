<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case PLANNED            = 'planned';
    case IN_PROGRESS        = 'in_progress';
    case COMPLETED          = 'completed';
    case ON_HOLD            = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::PLANNED           => 'Planned',
            self::IN_PROGRESS       => 'In Progress',
            self::COMPLETED         => 'Completed',
            self::ON_HOLD           => 'On Hold',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn($status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases()
        );
    }

    public function icon(): string
    {
        return match ($this) {
            self::PLANNED           => '📅',
            self::IN_PROGRESS       => '⏳',
            self::COMPLETED         => '✅',
            self::ON_HOLD           => '⏸️',
        };
    }
    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }
}
