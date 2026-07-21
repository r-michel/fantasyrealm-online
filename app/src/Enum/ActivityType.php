<?php

namespace App\Enum;

enum ActivityType: string
{
    case CHARACTER_CREATED = 'character_created';
    case CHARACTER_UPDATED = 'character_updated';
    case CHARACTER_DELETED = 'character_deleted';

    case APPEARANCE_UPDATED = 'appearance_updated';
    case EQUIPMENT_UPDATED = 'equipment_updated';

    case EQUIPMENT_CREATED = 'equipment_created';
    case EQUIPMENT_ACTIVATED = 'equipment_activated';
    case EQUIPMENT_DEACTIVATED = 'equipment_deactivated';
    case EQUIPMENT_DELETED = 'equipment_deleted';

    case CHARACTER_SHARED = 'character_shared';

    case CHARACTER_APPROVED = 'character_approved';
    case CHARACTER_REJECTED = 'character_rejected';


    case COMMENT_APPROVED = 'comment_approved';
    case COMMENT_REJECTED = 'comment_rejected';

    case USER_CREATED = 'user_created';
    case USER_SUSPENDED = 'user_suspended';
    case USER_UNSUSPENDED = 'user_unsuspended';
    case USER_DELETED = 'user_deleted';

    case EMPLOYEE_CREATED = 'employee_created';
    case EMPLOYEE_DELETED = 'employee_deleted';
    case EMPLOYEE_SUSPENDED = 'employee_suspended';
    case EMPLOYEE_UNSUSPENDED = 'employee_unsuspended';
}
