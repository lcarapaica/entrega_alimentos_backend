<?php

namespace App\Entity\Structure;

use App\Entity\Operations\Distribution;
use App\Repository\Structure\StationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a single delivery station  within a Distribution event at a specific Site.
 * Each site in a distribution can define its own set of stations with their own sequential order.
 *
 * @ORM\Entity(repositoryClass=StationRepository::class)
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(
 *             name="uq_station_distribution_site_order",
 *             columns={"distribution_id", "site_id", "order_number"}
 *         )
 *     }
 * )
 */
class Station
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Example: "Station 1 – Food Box", "Station 2 – School Kit"
     *
     * @ORM\Column(type="string", length=100)
     */
    private $name;

    /**
     * Sequential position of this station within the distribution+site.
     *
     * @ORM\Column(type="integer")
     */
    private $order_number;

    /**
     * The distribution event this station belongs to.
     *
     * @ORM\ManyToOne(targetEntity=Distribution::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $distribution;

    /**
     * @ORM\ManyToOne(targetEntity=Site::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $site;

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

    public function getOrderNumber(): ?int
    {
        return $this->order_number;
    }

    public function setOrderNumber(int $order_number): self
    {
        $this->order_number = $order_number;

        return $this;
    }

    public function getDistribution(): ?Distribution
    {
        return $this->distribution;
    }

    public function setDistribution(?Distribution $distribution): self
    {
        $this->distribution = $distribution;

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
