<?php

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a physical distribution site (headquarters/location) where
 * employees are assigned to collect their deliveries during a Distribution event.
 *
 * @ORM\Entity(repositoryClass=SiteRepository::class)
 */
class Site
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Human-readable name of the site.
     * Example: "Sede Principal Caracas", "Maracaibo"
     *
     * @ORM\Column(type="string", length=150)
     */
    private $name;

    /**
     * City where this site is located.
     *
     * @ORM\Column(type="string", length=100)
     */
    private $city;

    /**
     * Venezuelan state or region this site belongs to.
     * Example: "Distrito Capital", "Zulia", "Carabobo"
     *
     * @ORM\Column(type="string", length=100)
     */
    private $state;

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

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }
}
