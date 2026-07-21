<?php

namespace App\Factory\Activity;

use App\Entity\User;
use App\Enum\ActivityTargetType;
use App\Enum\ActivityType;
use App\Model\Activity;

final class EmployeeActivityFactory extends AbstractActivityFactory
{
    public function created(
        User $actor,
        User $employee,
    ): Activity {
        return $this->createActivity(
            type: ActivityType::EMPLOYEE_CREATED,
            actor: $actor,
            targetType: ActivityTargetType::EMPLOYEE,
            targetId: (string) $employee->getId(),
            targetName: $employee->getUsername(),
            message: sprintf(
                '%s a créé le compte employé %s.',
                $actor->getUsername(),
                $employee->getUsername(),
            ),
            details: [
                'roles' => $employee->getRoles(),
            ],
        );
    }

    /**
     * Cette méthode doit être appelée avant la suppression effective de
     * l'employé afin de conserver son identifiant et son nom d'utilisateur.
     */
    public function deleted(
        User $actor,
        User $employee,
    ): Activity {
        return $this->createActivity(
            type: ActivityType::EMPLOYEE_DELETED,
            actor: $actor,
            targetType: ActivityTargetType::EMPLOYEE,
            targetId: (string) $employee->getId(),
            targetName: $employee->getUsername(),
            message: sprintf(
                '%s a supprimé le compte employé %s.',
                $actor->getUsername(),
                $employee->getUsername(),
            ),
            details: [
                'roles' => $employee->getRoles(),
            ],
        );
    }

    public function suspended(
        User $actor,
        User $employee,
        ?string $reason = null,
    ): Activity {
        $details = [
            'suspended' => true,
        ];

        if ($reason !== null && $reason !== '') {
            $details['reason'] = $reason;
        }

        return $this->createActivity(
            type: ActivityType::EMPLOYEE_SUSPENDED,
            actor: $actor,
            targetType: ActivityTargetType::EMPLOYEE,
            targetId: (string) $employee->getId(),
            targetName: $employee->getUsername(),
            message: sprintf(
                '%s a suspendu le compte employé %s.',
                $actor->getUsername(),
                $employee->getUsername(),
            ),
            details: $details,
        );
    }

    public function unsuspended(
        User $actor,
        User $employee,
    ): Activity {
        return $this->createActivity(
            type: ActivityType::EMPLOYEE_UNSUSPENDED,
            actor: $actor,
            targetType: ActivityTargetType::EMPLOYEE,
            targetId: (string) $employee->getId(),
            targetName: $employee->getUsername(),
            message: sprintf(
                '%s a réactivé le compte employé %s.',
                $actor->getUsername(),
                $employee->getUsername(),
            ),
            details: [
                'suspended' => false,
            ],
        );
    }
}
