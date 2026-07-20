<?php

namespace App\Repository;

use App\Entity\Character;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @return Comment[]
     */
    public function findPublishedByCharacter(Character $character): array
    {
        return $this->createQueryBuilder('comment')
            ->andWhere('comment.onCharacter = :character')
            ->andWhere('comment.published = true')
            ->setParameter('character', $character)
            ->orderBy('comment.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByOwnerAndCharacter(
        User $owner,
        Character $character,
    ): ?Comment {
        return $this->createQueryBuilder('comment')
            ->andWhere('comment.owner = :owner')
            ->andWhere('comment.onCharacter = :character')
            ->setParameter('owner', $owner)
            ->setParameter('character', $character)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
