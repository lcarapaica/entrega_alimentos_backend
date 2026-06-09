<?php

namespace App\Repository\Operations;

use App\Entity\Operations\Distribution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Distribution>
 *
 * @method Distribution|null find($id, $lockMode = null, $lockVersion = null)
 * @method Distribution|null findOneBy(array $criteria, array $orderBy = null)
 * @method Distribution[]    findAll()
 * @method Distribution[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DistributionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Distribution::class);
    }

    /**
     * Returns the currently active (open) distribution, if one exists.
     * A distribution is open when its ended_at column is NULL.
     */
    public function findActiveDistribution(): ?Distribution
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.ended_at IS NULL')
            ->orderBy('d.started_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
