<?php

declare(strict_types=1);

namespace App\DTO\Staff;

use Symfony\Component\HttpFoundation\Request;

class EmployeeFilterDTO
{
    public string $search;
    public int    $page;
    public int    $limit;
    /** @var bool|null */
    public $isActive;
    public string $sort;
    public string $order;

    private function __construct(
        string $search,
        int    $page,
        int    $limit,
               $isActive,
        string $sort,
        string $order
    ) {
        $this->search   = $search;
        $this->page     = $page;
        $this->limit    = $limit;
        $this->isActive = $isActive;
        $this->sort     = $sort;
        $this->order    = $order;
    }

    /**
     * Builds the DTO from an incoming HTTP request.
     */
    public static function fromRequest(Request $request, bool $hasDistributionAccess = false): self
    {
        $search = trim((string) $request->query->get('search', ''));
        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = max(1, min(100, (int) $request->query->get('limit', 25)));

        // isActive: true | false | null (all)
        if (!$hasDistributionAccess) {
            $isActive = true; // Non-distribution admins are strictly limited to active employees
        } else {
            $isActiveRaw = $request->query->get('isActive', null);
            if ($isActiveRaw === null || $isActiveRaw === '') {
                $isActive = true; // default: only active
            } elseif (in_array(strtolower((string) $isActiveRaw), ['false', '0'], true)) {
                $isActive = false;
            } elseif (strtolower((string) $isActiveRaw) === 'all') {
                $isActive = null;
            } else {
                $isActive = true;
            }
        }

        // Whitelist sort fields
        $allowedSorts = ['id', 'full_name', 'national_id'];
        $sort  = in_array($request->query->get('sort', 'id'), $allowedSorts, true)
            ? $request->query->get('sort', 'id')
            : 'id';

        $order = strtoupper((string) $request->query->get('order', 'ASC')) === 'DESC'
            ? 'DESC'
            : 'ASC';

        return new self($search, $page, $limit, $isActive, $sort, $order);
    }

    /**
     * Returns the criteria array expected by EmployeeRepository::searchAndPaginate().
     */
    public function toArray(): array
    {
        return [
            'search'   => $this->search,
            'page'     => $this->page,
            'limit'    => $this->limit,
            'isActive' => $this->isActive,
            'sort'     => $this->sort,
            'order'    => $this->order,
        ];
    }
}
