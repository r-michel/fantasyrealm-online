<?php

namespace App\Presenter;

use App\Document\ActivityLog;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ActivityLogPresenter
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array{
     *     date: \DateTimeImmutable|\DateTimeInterface|null,
     *     actor: string,
     *     actorType: string,
     *     target: string,
     *     targetType: string,
     *     action: string,
     *     actionClass: string,
     *     detail: string,
     *     message: string
     * }
     */
    public function present(ActivityLog $activityLog): array
    {
        $type = $this->enumValue($activityLog->getType());
        $actorType = $this->enumValue($activityLog->getActorType());
        $targetType = $this->enumValue($activityLog->getTargetType());

        return [
            'date' => $activityLog->getCreatedAt(),
            'actor' => $activityLog->getActorUsername(),
            'actorType' => $this->getActorTypeLabel($actorType),
            'target' => $this->getTargetLabel(
                targetType: $targetType,
                targetName: $activityLog->getTargetName(),
            ),
            'targetType' => $this->getTargetTypeLabel($targetType),
            'action' => $this->getActionLabel($type),
            'actionClass' => $this->getActionClass($type),
            'detail' => $this->formatDetails(
                type: $type,
                details: $activityLog->getDetails(),
                fallback: $activityLog->getMessage(),
            ),
            'message' => $activityLog->getMessage(),
        ];
    }

    private function getActionLabel(string $type): string
    {
        return match ($type) {
            'character_created' => 'Création',
            'character_updated' => 'Identité',
            'character_deleted' => 'Suppression',
            'appearance_updated' => 'Apparence',
            'equipment_updated' => 'Équipement',
            'character_shared' => 'Partage',
            'character_approved' => 'Validation',
            'character_rejected' => 'Rejet',

            'comment_approved' => 'Validation',
            'comment_rejected' => 'Rejet',

            'user_suspended' => 'Suspension',
            'user_unsuspended' => 'Réactivation',
            'user_deleted' => 'Suppression',

            'employee_created' => 'Création',
            'employee_deleted' => 'Suppression',
            'employee_suspended' => 'Suspension',
            'employee_unsuspended' => 'Réactivation',

            'equipment_created' => 'Création',
            'equipment_activated' => 'Activation',
            'equipment_deactivated' => 'Désactivation',
            'equipment_deleted' => 'Suppression',

            default => 'Activité',
        };
    }

    private function getActionClass(string $type): string
    {
        return match ($type) {
            'character_created',
            'employee_created',
            'equipment_created' => 'activity-log-badge--created',

            'character_updated',
            'appearance_updated',
            'equipment_updated' => 'activity-log-badge--updated',

            'character_shared',
            'equipment_activated',
            'user_unsuspended',
            'employee_unsuspended' => 'activity-log-badge--positive',

            'character_approved',
            'comment_approved' => 'activity-log-badge--approved',

            'character_rejected',
            'comment_rejected',
            'user_suspended',
            'employee_suspended',
            'equipment_deactivated' => 'activity-log-badge--warning',

            'character_deleted',
            'user_deleted',
            'employee_deleted',
            'equipment_deleted' => 'activity-log-badge--deleted',

            default => 'activity-log-badge--default',
        };
    }

    private function getActorTypeLabel(string $actorType): string
    {
        return match ($actorType) {
            'admin' => 'Administrateur',
            'employee' => 'Employé',
            'user' => 'Utilisateur',
            default => ucfirst($actorType),
        };
    }

    private function getTargetTypeLabel(string $targetType): string
    {
        return match ($targetType) {
            'character' => 'Personnage',
            'comment' => 'Commentaire',
            'user' => 'Utilisateur',
            'employee' => 'Employé',
            'equipment' => 'Équipement',
            default => 'Cible',
        };
    }

    private function getTargetLabel(
        string $targetType,
        ?string $targetName,
    ): string {
        if ($targetName !== null && $targetName !== '') {
            return $targetName;
        }

        return $this->getTargetTypeLabel($targetType);
    }

    /**
     * @param array<string, mixed> $details
     */
    private function formatDetails(
        string $type,
        array $details,
        string $fallback,
    ): string {
        if ($details === []) {
            return $fallback;
        }

        return match ($type) {
            'character_created' => $this->formatCharacterCreated($details),

            'character_updated',
            'appearance_updated' => $this->formatChanges(
                $details['changes'] ?? [],
            ),

            'equipment_updated' => $this->formatEquipmentChanges(
                $details['changes'] ?? $details,
            ),

            'character_shared' => $this->formatBooleanState(
                value: $details['shared'] ?? null,
                trueLabel: 'Personnage rendu public',
                falseLabel: 'Personnage rendu privé',
                fallback: $fallback,
            ),

            'character_approved',
            'comment_approved' => 'Élément approuvé',

            'character_rejected',
            'comment_rejected' => $this->formatReason(
                prefix: 'Élément rejeté',
                details: $details,
            ),

            'user_suspended',
            'employee_suspended' => $this->formatReason(
                prefix: 'Compte suspendu',
                details: $details,
            ),

            'user_unsuspended',
            'employee_unsuspended' => 'Compte réactivé',

            'employee_created' => $this->formatRoles($details),
            'employee_deleted',
            'user_deleted' => $this->formatDeletedAccount($details),

            'equipment_created' => $this->formatBooleanState(
                value: $details['active'] ?? null,
                trueLabel: 'Équipement ajouté et activé',
                falseLabel: 'Équipement ajouté',
                fallback: $fallback,
            ),

            'equipment_activated' => 'Équipement rendu disponible',
            'equipment_deactivated' => 'Équipement retiré du builder',
            'equipment_deleted' => 'Équipement supprimé du catalogue',

            'character_deleted' => 'Personnage et données associées supprimés',

            default => $fallback,
        };
    }

    /**
     * @param array<string, mixed> $details
     */
    private function formatCharacterCreated(array $details): string
    {
        $parts = [];

        if (
            isset($details['name'])
            && is_string($details['name'])
            && $details['name'] !== ''
        ) {
            $parts[] = sprintf('Nom : %s', $details['name']);
        }

        if (
            isset($details['appearance'])
            && is_array($details['appearance'])
        ) {
            $appearance = $this->formatAppearance($details['appearance']);

            if ($appearance !== '') {
                $parts[] = $appearance;
            }
        }

        if (
            isset($details['equipment'])
            && is_array($details['equipment'])
        ) {
            $equipmentNames = $this->extractEquipmentNames(
                $details['equipment'],
            );

            if ($equipmentNames !== []) {
                $parts[] = sprintf(
                    'Équipements : %s',
                    implode(', ', $equipmentNames),
                );
            }
        }

        if (
            isset($details['duplicatedFrom'])
            && is_array($details['duplicatedFrom'])
        ) {
            $sourceName = $details['duplicatedFrom']['name'] ?? null;

            if (is_string($sourceName) && $sourceName !== '') {
                $parts[] = sprintf(
                    'Dupliqué depuis : %s',
                    $sourceName,
                );
            }
        }

        return $parts !== []
            ? implode(' · ', $parts)
            : 'Personnage créé';
    }

    /**
     * @param array<string, mixed> $appearance
     */
    private function formatAppearance(array $appearance): string
    {
        $values = [];

        foreach ($appearance as $field => $value) {
            if (
                $value === null
                || $value === ''
                || is_array($value)
                || is_object($value)
            ) {
                continue;
            }

            $values[] = sprintf(
                '%s : %s',
                $this->getFieldLabel((string) $field),
                $this->translateAppearanceValue($field, $value),
            );
        }

        if ($values === []) {
            return '';
        }

        return sprintf(
            'Apparence : %s',
            implode(', ', $values),
        );
    }

    /**
     * @param mixed $changes
     */
    private function formatChanges(mixed $changes): string
    {
        if (!is_array($changes) || $changes === []) {
            return 'Modification enregistrée';
        }

        $parts = [];

        foreach ($changes as $field => $change) {
            if (
                !is_array($change)
                || !array_key_exists('from', $change)
                || !array_key_exists('to', $change)
            ) {
                continue;
            }

            $fieldName = (string) $field;

            $parts[] = sprintf(
                '%s : %s → %s',
                $this->getFieldLabel($fieldName),
                $this->translateAppearanceValue(
                    $fieldName,
                    $change['from'],
                ),
                $this->translateAppearanceValue(
                    $fieldName,
                    $change['to'],
                ),
            );
        }

        return $parts !== []
            ? implode(' · ', $parts)
            : 'Modification enregistrée';
    }

    /**
     * @param mixed $changes
     */
    private function formatEquipmentChanges(mixed $changes): string
    {
        if (!is_array($changes)) {
            return 'Équipement modifié';
        }

        $parts = [];

        if (isset($changes['added']) && is_array($changes['added'])) {
            $addedNames = $this->extractEquipmentNames(
                $changes['added'],
            );

            if ($addedNames !== []) {
                $parts[] = sprintf(
                    'Ajout : %s',
                    implode(', ', $addedNames),
                );
            }
        }

        if (isset($changes['removed']) && is_array($changes['removed'])) {
            $removedNames = $this->extractEquipmentNames(
                $changes['removed'],
            );

            if ($removedNames !== []) {
                $parts[] = sprintf(
                    'Retrait : %s',
                    implode(', ', $removedNames),
                );
            }
        }

        if (
            $parts === []
            && isset($changes['equipmentName'])
            && is_string($changes['equipmentName'])
        ) {
            $parts[] = sprintf(
                'Équipement : %s',
                $changes['equipmentName'],
            );
        }

        return $parts !== []
            ? implode(' · ', $parts)
            : 'Équipement modifié';
    }

    /**
     * @param array<int|string, mixed> $equipment
     *
     * @return list<string>
     */
    private function extractEquipmentNames(array $equipment): array
    {
        $names = [];

        foreach ($equipment as $item) {
            if (is_string($item) && $item !== '') {
                $names[] = $item;

                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $name = $item['name'] ?? null;

            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param array<string, mixed> $details
     */
    private function formatReason(
        string $prefix,
        array $details,
    ): string {
        $reason = $details['reason'] ?? null;

        if (!is_string($reason) || $reason === '') {
            return $prefix;
        }

        return sprintf(
            '%s · Motif : %s',
            $prefix,
            $reason,
        );
    }

    /**
     * @param array<string, mixed> $details
     */
    private function formatRoles(array $details): string
    {
        $roles = $details['roles'] ?? null;

        if (!is_array($roles) || $roles === []) {
            return 'Compte employé créé';
        }

        return sprintf(
            'Rôles : %s',
            implode(
                ', ',
                array_map(
                    static fn (mixed $role): string => (string) $role,
                    $roles,
                ),
            ),
        );
    }

    /**
     * @param array<string, mixed> $details
     */
    private function formatDeletedAccount(array $details): string
    {
        $roles = $details['roles'] ?? null;

        if (!is_array($roles) || $roles === []) {
            return 'Compte supprimé';
        }

        return sprintf(
            'Compte supprimé · Rôles : %s',
            implode(
                ', ',
                array_map(
                    static fn (mixed $role): string => (string) $role,
                    $roles,
                ),
            ),
        );
    }

    private function formatBooleanState(
        mixed $value,
        string $trueLabel,
        string $falseLabel,
        string $fallback,
    ): string {
        if (!is_bool($value)) {
            return $fallback;
        }

        return $value ? $trueLabel : $falseLabel;
    }

    private function getFieldLabel(string $field): string
    {
        return match ($field) {
            'name' => 'Nom',
            'gender' => 'Genre',
            'skinColor' => 'Teinte',
            'hairColor' => 'Cheveux',
            'eyeColor' => 'Yeux',
            'eyeShape' => 'Forme des yeux',
            'noseShape' => 'Nez',
            'mouthShape' => 'Bouche',
            'shared' => 'Partage',
            'authorized' => 'Validation',
            default => ucfirst(
                preg_replace(
                    '/(?<!^)[A-Z]/',
                    ' $0',
                    $field,
                ) ?? $field,
            ),
        };
    }

    private function formatScalar(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Non défini';
        }

        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return 'Valeur modifiée';
    }

    private function enumValue(mixed $value): string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return strtolower($value->name);
        }

        return (string) $value;
    }

    private function translateAppearanceValue(
        string $field,
        mixed $value,
    ): string {
        if (!is_string($value)) {
            return $this->formatScalar($value);
        }

        return match ($field) {
            'gender' => $this->translator->trans(
                'appearance.gender.' . $value,
            ),

            'skinColor' => $this->translator->trans(
                'appearance.skinColor.' . $value,
            ),

            'hairColor' => $this->translator->trans(
                'appearance.hairColor.' . $value,
            ),

            'eyeColor' => $this->translator->trans(
                'appearance.eyeColor.' . $value,
            ),

            'eyeShape' => $this->translator->trans(
                'appearance.eyeShape.' . $value,
            ),

            'noseShape' => $this->translator->trans(
                'appearance.noseShape.' . $value,
            ),

            'mouthShape' => $this->translator->trans(
                'appearance.mouthShape.' . $value,
            ),

            default => $value,
        };
    }
}
