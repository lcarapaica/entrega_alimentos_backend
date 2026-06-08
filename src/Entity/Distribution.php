<?php

namespace App\Entity;

use App\Repository\DistributionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a food distribution event.
 * A Distribution is considered open when ended_at IS NULL.
 * Setting ended_at to a datetime closes the window permanently.
 *
 * @ORM\Entity(repositoryClass=DistributionRepository::class)
 */
class Distribution
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * Human-readable name for this distribution event.
     * Example: "Jornada Junio 2025"
     *
     * @ORM\Column(type="string", length=150)
     */
    private $name;

    /**
     * Optional descriptive notes about this distribution event.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * The datetime when this distribution window was opened.
     * Set automatically in constructor.
     *
     * @ORM\Column(type="datetime")
     */
    private $started_at;

    /**
     * The datetime when this distribution window was closed.
     * NULL means the window is still open.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $ended_at;

    /**
     * The administrative user who created/opened this distribution.
     *
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $created_by;

    public function __construct()
    {
        $this->started_at = new \DateTime();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->started_at;
    }

    public function setStartedAt(\DateTimeInterface $started_at): self
    {
        $this->started_at = $started_at;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeInterface
    {
        return $this->ended_at;
    }

    public function setEndedAt(?\DateTimeInterface $ended_at): self
    {
        $this->ended_at = $ended_at;

        return $this;
    }

    /**
     * Convenience method to check if this distribution window is currently open.
     * A window is open when ended_at has not been set.
     */
    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }

    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }

    public function setCreatedBy(?User $created_by): self
    {
        $this->created_by = $created_by;

        return $this;
    }
}
