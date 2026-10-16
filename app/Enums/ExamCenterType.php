<?php

namespace App\Enums;

enum ExamCenterType: string
{
    case SCHOOL         = 'school';
    case INSTITUTION    = 'institution';
    case CENTER         = 'center';


    public function label(): string
    {
        return match ($this) {
            self::SCHOOL            => _app('school'),
            self::INSTITUTION       => _app('institution'),
            self::CENTER            => _app('center'),
        };
    }
    public function icon(): string
    {
        return match ($this) {
            self::SCHOOL        => '🏫️',
            self::INSTITUTION   => '🎓️',
            self::CENTER        => '🏬️',
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
