<?php

namespace App\Enums;

enum AddressType: string
{
    case CONTACT        = 'contact';
    case SHIPPING       = 'shipping';
    case LEAVE_REQUEST  = 'leave_request';
    case ROUTE          = 'route';
    case FACILITY       = 'facility';
    case OTHER          = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CONTACT        => _app('contact'),
            self::SHIPPING       => _app('shipping'),
            self::LEAVE_REQUEST  => _app('leave_request'),
            self::ROUTE          => _app('route'),
            self::FACILITY       => _app('facility'),
            self::OTHER          => _app('other'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CONTACT        => '📞',
            self::SHIPPING       => '📦',
            self::LEAVE_REQUEST  => '📝',
            self::ROUTE          => '🚛',
            self::FACILITY       => '🏢',
            self::OTHER          => '❓',
        };
    }
    public function display(): string
    {
        return $this->icon() . ' ' . $this->label();
    }
}
