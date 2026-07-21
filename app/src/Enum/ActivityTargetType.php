<?php

namespace App\Enum;

enum ActivityTargetType: string
{
    case CHARACTER = 'character';
    case USER = 'user';
    case EMPLOYEE = 'employee';
    case COMMENT = 'comment';
    case EQUIPMENT = 'equipment';
    case SYSTEM = 'system';
}
