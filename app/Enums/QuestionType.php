<?php

namespace App\Enums;

enum QuestionType: string
{
    case OBJECTIVE                 = 'objective';
    case ESSAY            = 'essay';

    public function label(): string
    {
        return match ($this) {
            self::OBJECTIVE       => _app('objective'),
            self::ESSAY          => _app('essay'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OBJECTIVE       => '🟢',
            self::ESSAY           => '🔌',
        };
    }

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
