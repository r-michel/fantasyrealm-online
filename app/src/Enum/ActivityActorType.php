<?php

namespace App\Enum;

enum ActivityActorType: string
{
    case USER = 'user';
    case EMPLOYEE = 'employee';
    case ADMIN = 'admin';
    case SYSTEM = 'system';
}
