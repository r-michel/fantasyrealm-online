<?php

namespace App\Factory\Activity;

use App\Entity\User;
use App\Enum\ActivityActorType;
use App\Enum\ActivityTargetType;
use App\Enum\ActivityType;
use App\Model\Activity;

abstract class AbstractActivityFactory
{
    /**
     * @param array<string, mixed> $details
     */
    protected function createActivity(
        ActivityType $type,
        User $actor,
        ActivityTargetType $targetType,
        string $targetId,
        string $targetName,
        string $message,
        array $details = [],
    ): Activity {
        $activity = new Activity(
            type: $type,
            actorType: $this->resolveActorType($actor),
            message: $message,
        );

        $activity
            ->actor(
                id: (string) $actor->getId(),
                username: $actor->getUsername(),
            )
            ->target(
                type: $targetType,
                id: $targetId,
                name: $targetName,
            )
            ->details($details);

        return $activity;
    }

    private function resolveActorType(User $actor): ActivityActorType
    {
        if (in_array('ROLE_ADMIN', $actor->getRoles(), true)) {
            return ActivityActorType::ADMIN;
        }

        if (in_array('ROLE_EMPLOYEE', $actor->getRoles(), true)) {
            return ActivityActorType::EMPLOYEE;
        }

        return ActivityActorType::USER;
    }
}
