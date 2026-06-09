<?php

declare(strict_types=1);

namespace App\DTO\Staff;

use Symfony\Component\HttpFoundation\Request;

class UserFilterDTO
{
    public string $search;
    public int $page;
    public int $limit;
    public bool $hasAdminAccess;
    public ?string $role;
    public bool $isActive;
    public string $sort;
    public string $order;

    public static function fromRequest(Request $request, bool $hasAdminAccess): self
    {
        $input = new self();
        $input->search = (string) $request->query->get('search', '');
        $input->page = $request->query->getInt('page', 1);
        $input->limit = $request->query->getInt('limit', 25);
        $input->hasAdminAccess = $hasAdminAccess;
        $input->role = $request->query->get('role');
        if ($input->role === '') {
            $input->role = null;
        }

        $isActiveVal = $request->query->get('isActive', 'true');
        $input->isActive = ($isActiveVal === 'true' || $isActiveVal === '1');
        
        // Non-admins are forced to only see active records
        if (!$hasAdminAccess) {
            $input->isActive = true;
        }

        $input->sort = (string) $request->query->get('sort', 'id');
        $input->order = (string) $request->query->get('order', 'DESC');

        return $input;
    }

    public function toArray(): array
    {
        return [
            'search' => $this->search,
            'page' => $this->page,
            'limit' => $this->limit,
            'hasAdminAccess' => $this->hasAdminAccess,
            'role' => $this->role,
            'isActive' => $this->isActive,
            'sort' => $this->sort,
            'order' => $this->order,
        ];
    }
}
