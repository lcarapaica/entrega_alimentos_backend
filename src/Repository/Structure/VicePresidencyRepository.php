<?php

namespace App\Repository\Structure;

use App\Entity\Structure\VicePresidency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VicePresidency>
 *
 * @method VicePresidency|null find($id, $lockMode = null, $lockVersion = null)
 * @method VicePresidency|null findOneBy(array $criteria, array $orderBy = null)
 * @method VicePresidency[]    findAll()
 * @method VicePresidency[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VicePresidencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VicePresidency::class);
    }
}
