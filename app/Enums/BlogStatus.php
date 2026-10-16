<?php

namespace App\Enums;

enum BlogStatus: string
{
    case DRAFT                  = 'draft';
    case PUBLISHED              = 'published';
    case ARCHIVED               = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT         => _app('draft'),
            self::PUBLISHED     => _app('published'),
            self::ARCHIVED      => _app('archived'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT         => '😴',
            self::PUBLISHED     => '🤗',
            self::ARCHIVED      => '🗑️',
        };
    }

    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }

    public static function options(): array
    {
        return array_map(
            fn($type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases()
        );
    }
}
