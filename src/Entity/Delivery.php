<?php

namespace App\Entity;

use App\Repository\DeliveryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a single employee delivery record at a specific Station.
 * An employee can have one Delivery per Station (not one per Distribution),
 * since a Distribution at a Site may have multiple stations to pass through.
 *
 * Sequential progression enforcement is handled at the service layer, not here.
 *
 * @ORM\Entity(repositoryClass=DeliveryRepository::class)
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="uq_delivery_employee_station",
 *             columns={"employee_id", "station_id"}
 *         )
 *     }
 * )
 */
class Delivery
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * The employee who received this delivery.
     *
     * @ORM\ManyToOne(targetEntity=Employee::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $employee;

    /**
     * The specific station at which this delivery was processed.
     * The Distribution and Site are reachable via station->getDistribution() and station->getSite().
     *
     * @ORM\ManyToOne(targetEntity=Station::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $station;

    /**
     * The administrative user (operator) who submitted this delivery record.
     *
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $operator;

    /**
     * Disk path to the stored signature image (decoded from base64 at service layer).
     *
     * @ORM\Column(type="string", length=255)
     */
    private $signature_path;

    /**
     * The datetime this delivery record was created.
     * Set automatically in constructor.
     *
     * @ORM\Column(type="datetime")
     */
    private $delivered_at;

    public function __construct()
    {
        $this->delivered_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): self
    {
        $this->employee = $employee;

        return $this;
    }

    public function getStation(): ?Station
    {
        return $this->station;
    }

    public function setStation(?Station $station): self
    {
        $this->station = $station;

        return $this;
    }

    public function getOperator(): ?User
    {
        return $this->operator;
    }

    public function setOperator(?User $operator): self
    {
        $this->operator = $operator;

        return $this;
    }

    public function getSignaturePath(): ?string
    {
        return $this->signature_path;
    }

    public function setSignaturePath(string $signature_path): self
    {
        $this->signature_path = $signature_path;

        return $this;
    }

    public function getDeliveredAt(): ?\DateTimeInterface
    {
        return $this->delivered_at;
    }

    public function setDeliveredAt(\DateTimeInterface $delivered_at): self
    {
        $this->delivered_at = $delivered_at;

        return $this;
    }
}
