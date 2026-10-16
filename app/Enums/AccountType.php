<?php

namespace App\Enums;

enum AccountType: string
{
    case AGENT = 'agent';
    case STANDARD = 'standard';
    case GUEST = 'guest';
}
