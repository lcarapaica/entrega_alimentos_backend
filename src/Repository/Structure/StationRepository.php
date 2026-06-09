<?php

namespace App\Repository\Structure;

use App\Entity\Operations\Distribution;
use App\Entity\Structure\Site;
use App\Entity\Structure\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Station>
 *
 * @method Station|null find($id, $lockMode = null, $lockVersion = null)
 * @method Station|null findOneBy(array $criteria, array $orderBy = null)
 * @method Station[]    findAll()
 * @method Station[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Station::class);
    }

    /**
     * Returns all stations for a given Distribution at a given Site,
     * ordered by their sequential position (order_number ASC).
     *
     * @return Station[]
     */
    public function findByDistributionAndSite(Distribution $distribution, Site $site): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.distribution = :distribution')
            ->andWhere('s.site = :site')
            ->setParameter('distribution', $distribution)
            ->setParameter('site', $site)
            ->orderBy('s.order_number', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Returns all stations that precede the given station in the same Distribution+Site.
     *
     * @return Station[]
     */
    public function findPreviousStations(Station $station): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.distribution = :distribution')
            ->andWhere('s.site = :site')
            ->andWhere('s.order_number < :order')
            ->setParameter('distribution', $station->getDistribution())
            ->setParameter('site', $station->getSite())
            ->setParameter('order', $station->getOrderNumber())
            ->orderBy('s.order_number', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
