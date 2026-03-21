<?php

namespace App\Repository;

use App\Entity\VerificationToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerificationToken>
 */
class VerificationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerificationToken::class);
    }

    public function findByToken(string $token): ?VerificationToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    public function findValidTokenByUser(User $user): ?VerificationToken
    {
        return $this->createQueryBuilder('vt')
            ->where('vt.user = :user')
            ->andWhere('vt.usedAt IS NULL')
            ->andWhere('vt.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTime())
            ->orderBy('vt.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteExpiredTokens(): void
    {
        $this->createQueryBuilder('vt')
            ->delete()
            ->where('vt.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
