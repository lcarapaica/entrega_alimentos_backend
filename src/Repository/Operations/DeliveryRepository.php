<?php

namespace App\Repository\Operations;

use App\Entity\Operations\Delivery;
use App\Entity\Operations\Distribution;
use App\Entity\Staff\Employee;
use App\Entity\Structure\Site;
use App\Entity\Structure\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Delivery>
 *
 * @method Delivery|null find($id, $lockMode = null, $lockVersion = null)
 * @method Delivery|null findOneBy(array $criteria, array $orderBy = null)
 * @method Delivery[]    findAll()
 * @method Delivery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DeliveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Delivery::class);
    }

    /**
     * Checks whether an employee already has a Delivery record for a specific Station to prevent double claims.
     */
    public function existsForEmployeeAndStation(Employee $employee, Station $station): bool
    {
        $count = $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->andWhere('d.employee = :employee')
            ->andWhere('d.station = :station')
            ->setParameter('employee', $employee)
            ->setParameter('station', $station)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    /**
     * Returns all Station IDs for which an employee has a completed Delivery
     * within a given Distribution at a given Site.
     * Used by the sequential progression check.
     *
     * @return int[]
     */
    public function findCompletedStationIdsForEmployee(
        Employee $employee,
        Distribution $distribution,
        Site $site
    ): array {
        $rows = $this->createQueryBuilder('d')
            ->select('IDENTITY(d.station) AS station_id')
            ->join('d.station', 's')
            ->andWhere('d.employee = :employee')
            ->andWhere('s.distribution = :distribution')
            ->andWhere('s.site = :site')
            ->setParameter('employee', $employee)
            ->setParameter('distribution', $distribution)
            ->setParameter('site', $site)
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'station_id');
    }
}
