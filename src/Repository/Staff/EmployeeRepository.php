<?php

namespace App\Repository\Staff;

use App\Entity\Staff\Employee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Employee>
 *
 * @method Employee|null find($id, $lockMode = null, $lockVersion = null)
 * @method Employee|null findOneBy(array $criteria, array $orderBy = null)
 * @method Employee[]    findAll()
 * @method Employee[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function add(Employee $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Finds a single employee by their national ID (cédula).
     */
    public function findByNationalId(string $nationalId): ?Employee
    {
        return $this->findOneBy(['national_id' => $nationalId]);
    }

    /**
     * Loads every employee in a single query and returns them indexed by national_id.
     *
     * @return array<string, Employee>
     */
    public function findAllIndexedByNationalId(): array
    {
        /** @var Employee[] $all */
        $all = $this->findAll();

        $map = [];
        foreach ($all as $employee) {
            $map[$employee->getNationalId()] = $employee;
        }

        return $map;
    }

    /**
     * Returns the national_id of every currently active employee.
     * Used by the import service to diff against the incoming file and deactivate anyone who has been removed.
     *
     * @return string[]
     */
    public function findAllActiveNationalIds(): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('e.national_id')
            ->where('e.is_active = true')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'national_id');
    }

    /**
     * Bulk-deactivates all employees whose national_id is NOT in the provided list.
     * Called at the end of an import run to archive anyone missing from the source file.
     *
     * @param string[] $presentNationalIds National IDs seen in the import file
     * @return int Number of employees deactivated
     */
    public function deactivateMissingEmployees(array $presentNationalIds): int
    {
        if (empty($presentNationalIds)) {
            return 0;
        }

        return (int) $this->createQueryBuilder('e')
            ->update()
            ->set('e.is_active', ':inactive')
            ->where('e.national_id NOT IN (:ids)')
            ->andWhere('e.is_active = true')
            ->setParameter('inactive', false)
            ->setParameter('ids', $presentNationalIds)
            ->getQuery()
            ->execute();
    }

    /**
     * Filters, searches, and paginates the employee list based on a criteria array.
     *
     * Supported criteria keys:
     *   search   (string)  – matches full_name, national_id, or p00_code
     *   page     (int)     – 1-based page number
     *   limit    (int)     – items per page
     *   isActive (bool)    – filter by active status; null returns all
     *   sort     (string)  – field to sort by: id | full_name | national_id
     *   order    (string)  – ASC | DESC
     */
    public function searchAndPaginate(array $criteria): array
    {
        $criteria = array_merge([
            'search'   => '',
            'page'     => 1,
            'limit'    => 25,
            'isActive' => true,
            'sort'     => 'id',
            'order'    => 'ASC',
        ], $criteria);

        $criteria['search'] = trim($criteria['search']);
        extract($criteria);

        $qb = $this->createQueryBuilder('e')
            ->leftJoin('e.site', 's')
            ->leftJoin('e.user', 'u');

        // Active/inactive filter
        if ($isActive === true) {
            $qb->andWhere('e.is_active = true');
        } elseif ($isActive === false) {
            $qb->andWhere('e.is_active = false');
        }
        // null → no filter, return all

        // Multi-word search across full_name, national_id, p00_code
        if (!empty($search)) {
            $words = explode(' ', $search);
            $i = 0;
            foreach ($words as $word) {
                $word = trim($word);
                if ($word === '') {
                    continue;
                }
                $p = 'term_' . $i;
                $qb->andWhere(
                    $qb->expr()->orX(
                        "e.full_name LIKE :$p",
                        "e.national_id LIKE :$p",
                        "e.p00_code LIKE :$p"
                    )
                )->setParameter($p, '%' . $word . '%');
                $i++;
            }
        }

        // Safe sort-field whitelist
        $allowedSortFields = [
            'id'          => 'e.id',
            'full_name'   => 'e.full_name',
            'national_id' => 'e.national_id',
        ];
        $sortField = $allowedSortFields[$sort] ?? 'e.id';
        $direction = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($sortField, $direction);

        // Count total before applying pagination
        $countQb = clone $qb;
        $countQb->select('COUNT(e.id)');
        $totalItems = (int) $countQb->getQuery()->getSingleScalarResult();
        $totalPages  = (int) ceil($totalItems / $limit);

        // Fetch the requested page
        $qb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);
        $employees = $qb->getQuery()->getResult();

        // Serialize
        $data = [];
        foreach ($employees as $employee) {
            $data[] = $this->serializeEmployee($employee);
        }

        return [
            'data' => $data,
            'meta' => [
                'total_items'  => $totalItems,
                'total_pages'  => (int) max(1, $totalPages),
                'current_page' => (int) $page,
                'limit'        => (int) $limit,
            ],
        ];
    }

    /**
     * Returns a consistent associative array representation of an Employee.
     * Used by both searchAndPaginate() and the controller serialization helper.
     */
    public function serializeEmployee(\App\Entity\Staff\Employee $employee): array
    {
        $site = $employee->getSite();
        $user = $employee->getUser();

        return [
            'id'              => $employee->getId(),
            'national_id'     => $employee->getNationalId(),
            'p00_code'        => $employee->getP00Code(),
            'full_name'       => $employee->getFullName(),
            'job_title'       => $employee->getJobTitle(),
            'vice_presidency' => $employee->getVicePresidency(),
            'department'      => $employee->getDepartment(),
            'is_active'       => $employee->getIsActive(),
            'foto_path'       => $employee->getFotoPath(),
            'site'            => $site ? [
                'id'     => $site->getId(),
                'name'   => $site->getName(),
                'region' => $site->getRegion(),
                'state'  => $site->getState(),
            ] : null,
            'user'            => $user ? [
                'id'    => $user->getId(),
                'email' => $user->getEmail(),
            ] : null,
        ];
    }
}
