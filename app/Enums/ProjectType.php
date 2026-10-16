<?php

namespace App\Enums;

enum ProjectType: string
{
    case CONTRACT           = 'contract';
    case DOCUMENTATION      = 'documentation';

    public function label(): string
    {
        return match ($this) {
            self::CONTRACT          => 'Contract',
            self::DOCUMENTATION     => 'Documentation',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn($type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases()
        );
    }

    public function icon(): string
    {
        return match ($this) {
            self::CONTRACT          => '📄',
            self::DOCUMENTATION     => '📚',
        };
    }
    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }
}
