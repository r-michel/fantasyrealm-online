<?php

namespace App\Repository;

use App\Entity\Character;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Character>
 */
class CharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Character::class);
    }

    /**
     * @return Character[]
     */
    public function findPublicCharacters(
        ?string $gender = null,
        ?\DateTimeImmutable $createdAfter = null,
        ?\DateTimeImmutable $createdBefore = null,
        ?string $creatorPseudo = null,
    ): array {
        $queryBuilder = $this->createQueryBuilder('character')
            ->innerJoin('character.owner', 'owner')
            ->addSelect('owner')
            ->andWhere('character.shared = :shared')
            ->andWhere('character.authorized = :authorized')
            ->setParameter('shared', true)
            ->setParameter('authorized', true)
            ->orderBy('character.createdAt', 'DESC');

        if ($gender !== null && $gender !== '') {
            $queryBuilder
                ->andWhere('character.gender = :gender')
                ->setParameter('gender', $gender);
        }

        if ($createdAfter !== null) {
            $queryBuilder
                ->andWhere('character.createdAt >= :createdAfter')
                ->setParameter('createdAfter', $createdAfter);
        }

        if ($createdBefore !== null) {
            $queryBuilder
                ->andWhere('character.createdAt <= :createdBefore')
                ->setParameter(
                    'createdBefore',
                    $createdBefore->setTime(23, 59, 59),
                );
        }

        if ($creatorPseudo !== null && $creatorPseudo !== '') {
            $queryBuilder
                ->andWhere(
                    'LOWER(owner.username) LIKE LOWER(:creatorPseudo)'
                )
                ->setParameter(
                    'creatorPseudo',
                    '%' . trim($creatorPseudo) . '%',
                );
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
}
