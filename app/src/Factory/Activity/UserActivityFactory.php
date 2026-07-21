<?php

namespace App\Factory\Activity;

use App\Entity\User;
use App\Enum\ActivityTargetType;
use App\Enum\ActivityType;
use App\Model\Activity;

final class UserActivityFactory extends AbstractActivityFactory
{
    public function suspended(
        User $actor,
        User $target,
        ?string $reason = null,
    ): Activity {
        $details = [
            'suspended' => true,
        ];

        if ($reason !== null && $reason !== '') {
            $details['reason'] = $reason;
        }

        return $this->createActivity(
            type: ActivityType::USER_SUSPENDED,
            actor: $actor,
            targetType: ActivityTargetType::USER,
            targetId: (string) $target->getId(),
            targetName: $target->getUsername(),
            message: sprintf(
                '%s a suspendu le compte de %s.',
                $actor->getUsername(),
                $target->getUsername(),
            ),
            details: $details,
        );
    }

    public function unsuspended(
        User $actor,
        User $target,
    ): Activity {
        return $this->createActivity(
            type: ActivityType::USER_UNSUSPENDED,
            actor: $actor,
            targetType: ActivityTargetType::USER,
            targetId: (string) $target->getId(),
            targetName: $target->getUsername(),
            message: sprintf(
                '%s a réactivé le compte de %s.',
                $actor->getUsername(),
                $target->getUsername(),
            ),
            details: [
                'suspended' => false,
            ],
        );
    }

    public function deleted(
        User $actor,
        User $target,
    ): Activity {
        return $this->createActivity(
            type: ActivityType::USER_DELETED,
            actor: $actor,
            targetType: ActivityTargetType::USER,
            targetId: (string) $target->getId(),
            targetName: $target->getUsername(),
            message: sprintf(
                '%s a supprimé le compte de %s.',
                $actor->getUsername(),
                $target->getUsername(),
            ),
            details: [
                'roles' => $target->getRoles(),
            ],
        );
    }
}
