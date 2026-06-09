<?php

namespace App\Entity\Structure;

use App\Entity\Staff\Employee;
use App\Repository\Structure\DepartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * 
 * @ORM\Entity(repositoryClass=DepartmentRepository::class)
 */
class Department
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=VicePresidency::class, inversedBy="departments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $vice_presidency;

    /**
     * @ORM\OneToMany(targetEntity=Employee::class, mappedBy="department")
     */
    private $employees;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getVicePresidency(): ?VicePresidency
    {
        return $this->vice_presidency;
    }

    public function setVicePresidency(?VicePresidency $vice_presidency): self
    {
        $this->vice_presidency = $vice_presidency;

        return $this;
    }

    /**
     * @return Collection|Employee[]
     */
    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function addEmployee(Employee $employee): self
    {
        if (!$this->employees->contains($employee)) {
            $this->employees[] = $employee;
            $employee->setDepartment($this);
        }

        return $this;
    }

    public function removeEmployee(Employee $employee): self
    {
        if ($this->employees->removeElement($employee)) {
            if ($employee->getDepartment() === $this) {
                $employee->setDepartment(null);
            }
        }

        return $this;
    }
}
