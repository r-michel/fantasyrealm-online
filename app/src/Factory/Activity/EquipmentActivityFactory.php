<?php

namespace App\Factory\Activity;

use App\Entity\Equipment;
use App\Entity\User;
use App\Enum\ActivityTargetType;
use App\Enum\ActivityType;
use App\Model\Activity;

final class EquipmentActivityFactory extends AbstractActivityFactory
{
    public function created(
        User $actor,
        Equipment $equipment,
    ): Activity {
        return $this->createActivity(
            type: ActivityType::EQUIPMENT_CREATED,
            actor: $actor,
            targetType: ActivityTargetType::EQUIPMENT,
            targetId: (string) $equipment->getId(),
            targetName: $equipment->getName(),
            message: sprintf(
                '%s a ajouté l’équipement %s au catalogue.',
                $actor->getUsername(),
                $equipment->getName(),
            ),
            details: [
                'active' => $equipment->isActive(),
            ],
        );
    }

    public function activated(
        User $actor,
        Equipment $equipment,
    ): Activity {
        return $this->createActivity(
            type: ActivityType::EQUIPMENT_ACTIVATED,
            actor: $actor,
            targetType: ActivityTargetType::EQUIPMENT,
            targetId: (string) $equipment->getId(),
            targetName: $equipment->getName(),
            message: sprintf(
                '%s a activé l’équipement %s.',
                $actor->getUsername(),
                $equipment->getName(),
            ),
            details: [
                'active' => true,
            ],
        );
    }

    public function deactivated(
        User $actor,
        Equipment $equipment,
    ): Activity {
        return $this->createActivity(
            type: ActivityType::EQUIPMENT_DEACTIVATED,
            actor: $actor,
            targetType: ActivityTargetType::EQUIPMENT,
            targetId: (string) $equipment->getId(),
            targetName: $equipment->getName(),
            message: sprintf(
                '%s a désactivé l’équipement %s.',
                $actor->getUsername(),
                $equipment->getName(),
            ),
            details: [
                'active' => false,
            ],
        );
    }

    public function deleted(
        User $actor,
        Equipment $equipment,
    ): Activity {
        return $this->createActivity(
            type: ActivityType::EQUIPMENT_DELETED,
            actor: $actor,
            targetType: ActivityTargetType::EQUIPMENT,
            targetId: (string) $equipment->getId(),
            targetName: $equipment->getName(),
            message: sprintf(
                '%s a supprimé l’équipement %s du catalogue.',
                $actor->getUsername(),
                $equipment->getName(),
            ),
            details: [
                'active' => $equipment->isActive(),
            ],
        );
    }
}
