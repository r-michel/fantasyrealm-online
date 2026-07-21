<?php

namespace App\Repository;

use App\Document\ActivityLog;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * @extends DocumentRepository<ActivityLog>
 */
final class ActivityLogRepository extends DocumentRepository
{
    /**
     * Retourne les derniers logs.
     *
     * @return ActivityLog[]
     */
    public function findLatest(int $limit = 50): array
    {
        return $this->createQueryBuilder()
            ->sort('createdAt', 'DESC')
            ->limit($limit)
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * @return ActivityLog[]
     */
    public function findByCharacter(string $publicId): array
    {
        return $this->createQueryBuilder()
            ->field('characterPublicId')->equals($publicId)
            ->sort('createdAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }

    /**
     * @return ActivityLog[]
     */
    public function findByActor(string $actorId): array
    {
        return $this->createQueryBuilder()
            ->field('actorId')->equals($actorId)
            ->sort('createdAt', 'DESC')
            ->getQuery()
            ->execute()
            ->toArray();
    }
}
