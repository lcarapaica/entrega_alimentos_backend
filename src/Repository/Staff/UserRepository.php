<?php

namespace App\Repository\Staff;

use App\Entity\Staff\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function add(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Filters, searches, and paginates the users list based on criteria array.
     */
    public function searchAndPaginate(array $criteria): array
    {
        // Set fallback default values for missing keys
        $criteria = array_merge([
            'search'         => '',
            'page'           => 1,
            'limit'          => 25,
            'hasAdminAccess' => false,
            'role'           => null,
            'isActive'       => true,
            'sort'           => 'id',
            'order'          => 'DESC',
        ], $criteria);

        // Prepares array keys as local variables ($search, $page, $limit, etc.)
        $criteria['search'] = trim($criteria['search']);
        extract($criteria);
        
        // Start building the query with alias 'u' and left join employee 'e'
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.employee', 'e');

        // Activity filter: non-admins are forced to only see active records
        if (!$hasAdminAccess || $isActive === true) {
            $qb->andWhere('u.deleted_at IS NULL');
        } elseif ($isActive === false) {
            $qb->andWhere('u.deleted_at IS NOT NULL');
        }

        // Role category filter
        if ($role !== null && $role !== '') {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%"' . $role . '"%');
        }

        // Multi-word search matching email only
        if (!empty($search)) {
            $words = explode(' ', $search);
            $i = 0;
            foreach ($words as $word) {
                $word = trim($word);
                if ($word === '') continue;

                $paramName = 'term_' . $i;
                $qb->andWhere("u.email LIKE :$paramName")
                    ->setParameter($paramName, '%' . $word . '%');
                $i++;
            }
        }

        // Dynamic sorting with a safe field whitelist check
        $allowedSortFields = [
            'id'    => 'u.id',
            'email' => 'u.email',
        ];
        $sortField = $allowedSortFields[$sort] ?? 'u.id';
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy($sortField, $order);

        // Pagination: Get total items count first using a cloned query builder
        $countQb = clone $qb;
        $countQb->select('COUNT(u.id)');
        $totalItems = (int) $countQb->getQuery()->getSingleScalarResult();
        $totalPages = (int) ceil($totalItems / $limit);

        // Fetch paginated users
        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);
        $users = $qb->getQuery()->getResult();

        // Map database objects into a clean associative array
        $data = [];
        foreach ($users as $user) {
            $roles = $user->getRoles();
            $role = count($roles) > 0 ? $roles[0] : 'ROLE_USER';

            // Hide superuser role from non-admins
            if ($role === 'ROLE_SUPER_ADMIN' && !$hasAdminAccess) {
                $role = 'ROLE_ADMIN';
            }

            $employee = $user->getEmployee();
            $employeeData = null;
            if ($employee !== null) {
                $employeeData = [
                    'id'          => $employee->getId(),
                    'national_id' => $employee->getNationalId(),
                    'p00_code'    => $employee->getP00Code(),
                    'full_name'   => $employee->getFullName(),
                    'foto_path'   => $employee->getFotoPath(),
                ];
            }

            $userArray = [
                'id'        => $user->getId(),
                'email'     => $user->getEmail(),
                'full_name' => $employee ? $employee->getFullName() : null,
                'role'      => $role,
                'isActive' => $user->getIsActive(),
                'mustChangePassword' => $user->getMustChangePassword(),
                'registeredAt' => $user->getRegisteredAt() ? $user->getRegisteredAt()->format('Y-m-d H:i:s') : null,
                'employee' => $employeeData,
            ];

            // Only add deletedAt field if current visitor has admin privileges
            if ($hasAdminAccess) {
                $userArray['deletedAt'] = $user->getDeletedAt() ? $user->getDeletedAt()->format('Y-m-d H:i:s') : null;
            }

            $data[] = $userArray;
        }

        // Return structured dataset paired with standard pagination metrics
        return [
            'data' => $data,
            'meta' => [
                'total_items'  => $totalItems,
                'total_pages'  => (int)max(1, $totalPages),
                'current_page' => (int)$page,
                'limit'        => (int)$limit
            ]
        ];
    }
}
