<?php

namespace App\Factory\Activity;

use App\Entity\Character;
use App\Entity\User;
use App\Enum\ActivityTargetType;
use App\Enum\ActivityType;
use App\Model\Activity;

final class CharacterActivityFactory extends AbstractActivityFactory
{
    /**
     * @param array<string, mixed> $details
     */
    public function created(
        User $actor,
        Character $character,
        array $details = [],
    ): Activity {
        return $this->createActivity(
            type: ActivityType::CHARACTER_CREATED,
            actor: $actor,
            targetType: ActivityTargetType::CHARACTER,
            targetId: $character->getPublicId(),
            targetName: $character->getName(),
            message: sprintf(
                '%s a créé le personnage %s.',
                $actor->getUsername(),
                $character->getName(),
            ),
            details: $details,
        );
    }

    /**
     * @param array<string, mixed> $changes
     */
    public function updated(
        User $actor,
        Character $character,
        array $changes = [],
    ): Activity {
        return $this->createActivity(
            type: ActivityType::CHARACTER_UPDATED,
            actor: $actor,
            targetType: ActivityTargetType::CHARACTER,
            targetId: $character->getPublicId(),
            targetName: $character->getName(),
            message: sprintf(
                '%s a modifié le personnage %s.',
                $actor->getUsername(),
                $character->getName(),
            ),
            details: [
                'changes' => $changes,
            ],
        );
    }

    /**
     * @param array<string, mixed> $details
     */
    public function deleted(
        User $actor,
        Character $character,
        array $details = [],
    ): Activity {
        return $this->createActivity(
            type: ActivityType::CHARACTER_DELETED,
            actor: $actor,
            targetType: ActivityTargetType::CHARACTER,
            targetId: $character->getPublicId(),
            targetName: $character->getName(),
            message: sprintf(
                '%s a supprimé le personnage %s.',
                $actor->getUsername(),
                $character->getName(),
            ),
            details: $details,
        );
    }

    /**
     * @param array<string, mixed> $changes
     */
    public function appearanceUpdated(
        User $actor,
        Character $character,
        array $changes = [],
    ): Activity {
        return $this->createActivity(
            type: ActivityType::APPEARANCE_UPDATED,
            actor: $actor,
            targetType: ActivityTargetType::CHARACTER,
            targetId: $character->getPublicId(),
            targetName: $character->getName(),
            message: sprintf(
                '%s a modifié l’apparence du personnage %s.',
                $actor->getUsername(),
                $character->getName(),
            ),
            details: [
                'changes' => $changes,
            ],
        );
    }

    /**
     * @param array<string, mixed> $changes
     */
    public function equipmentUpdated(
        User $actor,
        Character $character,
        array $changes = [],
    ): Activity {
        return $this->createActivity(
            type: ActivityType::EQUIPMENT_UPDATED,
            actor: $actor,
            targetType: ActivityTargetType::CHARACTER,
            targetId: $character->getPublicId(),
            targetName: $character->getName(),
            message: sprintf(
                '%s a modifié l’équipement de %s.',
                $actor->getUsername(),
                $character->getName(),
            ),
            details: [
                'changes' => $changes,
            ],
        );
    }

    /**
     * @param array<string, mixed> $details
     */
    public function shared(
        User $actor,
        Character $character,
        array $details = [],
    ): Activity {
        return $this->createActivity(
            type: ActivityType::CHARACTER_SHARED,
            actor: $actor,
            targetType: ActivityTargetType::CHARACTER,
            targetId: $character->getPublicId(),
            targetName: $character->getName(),
            message: sprintf(
                '%s a partagé le personnage %s.',
                $actor->getUsername(),
                $character->getName(),
            ),
            details: $details,
        );
    }

    public function approved(
        User $actor,
        Character $character,
    ): Activity {
        return $this->createActivity(
            type: ActivityType::CHARACTER_APPROVED,
            actor: $actor,
            targetType: ActivityTargetType::CHARACTER,
            targetId: $character->getPublicId(),
            targetName: $character->getName(),
            message: sprintf(
                '%s a approuvé le personnage %s.',
                $actor->getUsername(),
                $character->getName(),
            ),
            details: [
                'status' => 'approved',
            ],
        );
    }

    public function rejected(
        User $actor,
        Character $character,
        ?string $reason = null,
    ): Activity {
        $details = [
            'status' => 'rejected',
        ];

        if ($reason !== null && $reason !== '') {
            $details['reason'] = $reason;
        }

        return $this->createActivity(
            type: ActivityType::CHARACTER_REJECTED,
            actor: $actor,
            targetType: ActivityTargetType::CHARACTER,
            targetId: $character->getPublicId(),
            targetName: $character->getName(),
            message: sprintf(
                '%s a rejeté le personnage %s.',
                $actor->getUsername(),
                $character->getName(),
            ),
            details: $details,
        );
    }
}
