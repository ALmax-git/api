<?php

namespace App\Enums;

enum CommentStatus: string
{
    case ACCEPTED           = 'accepted';
    case PENDING            = 'pending';
    case REJECTED           = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::ACCEPTED          => _app('accepted'),
            self::PENDING           => _app('pending'),
            self::REJECTED          => _app('setup'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACCEPTED          => '🤗',
            self::PENDING           => '😴',
            self::REJECTED          => '🥲',
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
