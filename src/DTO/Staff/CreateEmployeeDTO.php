<?php

declare(strict_types=1);

namespace App\DTO\Staff;

use Symfony\Component\Validator\Constraints as Assert;

class CreateEmployeeDTO
{
    /**
     * @Assert\NotBlank(message="national_id is required.")
     * @Assert\Length(max=20, maxMessage="national_id may not exceed 20 characters.")
     */
    public ?string $national_id;

    /**
     * @Assert\NotBlank(message="full_name is required.")
     * @Assert\Length(max=255, maxMessage="full_name may not exceed 255 characters.")
     */
    public ?string $full_name;

    /**
     * @Assert\Length(max=20, maxMessage="p00_code may not exceed 20 characters.")
     */
    public ?string $p00_code;

    /**
     * @Assert\Length(max=255, maxMessage="job_title may not exceed 255 characters.")
     */
    public ?string $job_title;

    /**
     * @Assert\Length(max=255, maxMessage="vice_presidency may not exceed 255 characters.")
     */
    public ?string $vice_presidency;

    /**
     * @Assert\Length(max=255, maxMessage="department may not exceed 255 characters.")
     */
    public ?string $department;

    /** @Assert\Type(type="integer") */
    public ?int $site_id;

    public function __construct(array $data)
    {
        $this->national_id     = isset($data['national_id'])     ? trim((string) $data['national_id'])     : null;
        $this->full_name       = isset($data['full_name'])       ? trim((string) $data['full_name'])       : null;
        $this->p00_code        = isset($data['p00_code'])        ? trim((string) $data['p00_code'])        : null;
        $this->job_title       = isset($data['job_title'])       ? trim((string) $data['job_title'])       : null;
        $this->vice_presidency = isset($data['vice_presidency']) ? trim((string) $data['vice_presidency']) : null;
        $this->department      = isset($data['department'])      ? trim((string) $data['department'])      : null;
        $this->site_id         = isset($data['site_id'])         ? (int) $data['site_id']                 : null;

        // Normalize empty strings back to null for nullable fields
        $this->p00_code        = ($this->p00_code === '')        ? null : $this->p00_code;
        $this->job_title       = ($this->job_title === '')       ? null : $this->job_title;
        $this->vice_presidency = ($this->vice_presidency === '') ? null : $this->vice_presidency;
        $this->department      = ($this->department === '')      ? null : $this->department;
    }
}
