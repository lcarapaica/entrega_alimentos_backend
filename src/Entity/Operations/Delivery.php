<?php

namespace App\Entity\Operations;

use App\Entity\Staff\Employee;
use App\Entity\Staff\User;
use App\Entity\Structure\Station;
use App\Repository\Operations\DeliveryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a single employee delivery record at a specific Station,
 * An employee can have one Delivery per Station (not one per Distribution).
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
     *
     * @ORM\ManyToOne(targetEntity=Employee::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $employee;

    /**
     *
     * @ORM\ManyToOne(targetEntity=Station::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $station;

    /**
     *
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $operator;

    /**
     * Disk path to the stored signature image (decoded from base64).
     *
     * @ORM\Column(type="string", length=255)
     */
    private $signature_path;

    /**
     *
     * @ORM\Column(type="datetime")
     */
    private $delivered_at;

    /**
     *
     * @ORM\Column(type="boolean")
     */
    private $is_proxy_delivery;

    /**
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $authorized_cedula;

    /**
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $authorized_full_name;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $authorization_reason;

    public function __construct()
    {
        $this->delivered_at = new \DateTime();
        $this->is_proxy_delivery = false;
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

    public function getIsProxyDelivery(): bool
    {
        return $this->is_proxy_delivery;
    }

    public function setIsProxyDelivery(bool $is_proxy_delivery): self
    {
        $this->is_proxy_delivery = $is_proxy_delivery;

        return $this;
    }

    public function getAuthorizedCedula(): ?string
    {
        return $this->authorized_cedula;
    }

    public function setAuthorizedCedula(?string $authorized_cedula): self
    {
        $this->authorized_cedula = $authorized_cedula;

        return $this;
    }

    public function getAuthorizedFullName(): ?string
    {
        return $this->authorized_full_name;
    }

    public function setAuthorizedFullName(?string $authorized_full_name): self
    {
        $this->authorized_full_name = $authorized_full_name;

        return $this;
    }

    public function getAuthorizationReason(): ?string
    {
        return $this->authorization_reason;
    }

    public function setAuthorizationReason(?string $authorization_reason): self
    {
        $this->authorization_reason = $authorization_reason;

        return $this;
    }
}
