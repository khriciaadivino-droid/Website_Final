<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    /**
     * Find logs with optional filters
     * 
     * @param array $filters Array with optional keys: userId, action, dateFrom, dateTo
     * @param string $orderBy Field to order by (default: timestamp)
     * @param string $orderDirection ASC or DESC (default: DESC)
     * @return ActivityLog[]
     */
    public function findWithFilters(array $filters = [], string $orderBy = 'timestamp', string $orderDirection = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('a');

        if (!empty($filters['userId'])) {
            $qb->andWhere('a.userId = :userId')
               ->setParameter('userId', $filters['userId']);
        }

        if (!empty($filters['username'])) {
            $qb->andWhere('a.username LIKE :username')
               ->setParameter('username', '%' . $filters['username'] . '%');
        }

        if (!empty($filters['action'])) {
            $qb->andWhere('a.action = :action')
               ->setParameter('action', $filters['action']);
        }

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('a.timestamp >= :dateFrom')
               ->setParameter('dateFrom', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            // Add one day to include the entire end date
            $dateTo = clone $filters['dateTo'];
            $dateTo->modify('+1 day');
            $qb->andWhere('a.timestamp < :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        $qb->orderBy('a.' . $orderBy, $orderDirection);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all distinct actions for filtering
     * 
     * @return array
     */
    public function findDistinctActions(): array
    {
        $results = $this->createQueryBuilder('a')
            ->select('DISTINCT a.action')
            ->orderBy('a.action', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($results, 'action');
    }

    /**
     * Get all distinct usernames for filtering
     * 
     * @return array
     */
    public function findDistinctUsernames(): array
    {
        $results = $this->createQueryBuilder('a')
            ->select('DISTINCT a.username')
            ->orderBy('a.username', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($results, 'username');
    }

    /**
     * Count logs by action type
     * 
     * @return array
     */
    public function countByAction(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.action, COUNT(a.id) as count')
            ->groupBy('a.action')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent logs
     * 
     * @param int $limit
     * @return ActivityLog[]
     */
    public function findRecentLogs(int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.timestamp', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
