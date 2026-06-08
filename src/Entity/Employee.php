<?php

namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EmployeeRepository::class)
 */
class Employee
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=20, unique=true)
     */
    private $national_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $first_name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $last_name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $is_active;

    /**
     * @ORM\ManyToOne(targetEntity=JobTitle::class, inversedBy="employees")
     * @ORM\JoinColumn(nullable=false)
     */
    private $job_title;

    /**
     * @ORM\ManyToOne(targetEntity=Department::class, inversedBy="employees")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $department;

    /**
     * @ORM\OneToOne(targetEntity=User::class, mappedBy="employee", cascade={"persist"})
     */
    private $user;

    /**
     * Profile photo path. If null, a photo capture is required on next delivery.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $foto_path;

    /**
     * The physical distribution site this employee is assigned to.
     * Determines which Site they report to during a Distribution event.
     *
     * @ORM\ManyToOne(targetEntity=Site::class)
     * @ORM\JoinColumn(nullable=true)
     */
    private $site;

    public function __construct()
    {
        $this->is_active = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNationalId(): ?string
    {
        return $this->national_id;
    }

    public function setNationalId(string $national_id): self
    {
        $this->national_id = $national_id;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): self
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getJobTitle(): ?JobTitle
    {
        return $this->job_title;
    }

    public function setJobTitle(?JobTitle $job_title): self
    {
        $this->job_title = $job_title;

        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): self
    {
        $this->department = $department;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        // Unset the owning side of the relation if necessary
        if ($user === null && $this->user !== null) {
            $this->user->setEmployee(null);
        }

        // Set the owning side of the relation if necessary
        if ($user !== null && $user->getEmployee() !== $this) {
            $user->setEmployee($this);
        }

        $this->user = $user;

        return $this;
    }

    public function getFotoPath(): ?string
    {
        return $this->foto_path;
    }

    public function setFotoPath(?string $foto_path): self
    {
        $this->foto_path = $foto_path;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): self
    {
        $this->site = $site;

        return $this;
    }
}
