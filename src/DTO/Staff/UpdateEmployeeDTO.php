<?php

declare(strict_types=1);

namespace App\DTO\Staff;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateEmployeeDTO
{
    /**
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

    /**
     * Tracks which fields were explicitly provided in the request so the
     * controller can skip unset fields rather than overwriting with null.
     */
    public array $provided = [];

    public function __construct(array $data)
    {
        if (array_key_exists('full_name', $data)) {
            $this->full_name   = trim((string) $data['full_name']) ?: null;
            $this->provided[]  = 'full_name';
        } else {
            $this->full_name = null;
        }

        if (array_key_exists('p00_code', $data)) {
            $this->p00_code   = trim((string) $data['p00_code']) ?: null;
            $this->provided[] = 'p00_code';
        } else {
            $this->p00_code = null;
        }

        if (array_key_exists('job_title', $data)) {
            $this->job_title   = trim((string) $data['job_title']) ?: null;
            $this->provided[]  = 'job_title';
        } else {
            $this->job_title = null;
        }

        if (array_key_exists('vice_presidency', $data)) {
            $this->vice_presidency = trim((string) $data['vice_presidency']) ?: null;
            $this->provided[]      = 'vice_presidency';
        } else {
            $this->vice_presidency = null;
        }

        if (array_key_exists('department', $data)) {
            $this->department  = trim((string) $data['department']) ?: null;
            $this->provided[]  = 'department';
        } else {
            $this->department = null;
        }

        if (array_key_exists('site_id', $data)) {
            $this->site_id    = $data['site_id'] !== null ? (int) $data['site_id'] : null;
            $this->provided[] = 'site_id';
        } else {
            $this->site_id = null;
        }
    }
}
