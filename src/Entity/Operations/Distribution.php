<?php

namespace App\Entity\Operations;

use App\Entity\Staff\User;
use App\Repository\Operations\DistributionRepository;
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
     *
     * @ORM\Column(type="string", length=150)
     */
    private $name;

    /**
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     *
     * @ORM\Column(type="datetime")
     */
    private $started_at;

    /**
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $ended_at;

    /**
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
