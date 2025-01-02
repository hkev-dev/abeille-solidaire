<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\ProjectRewardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRewardRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProjectReward
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null {
        get {
            return $this->id;
        }
    }

    #[ORM\ManyToOne(inversedBy: 'rewards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column]
    private float $amount = 0.0;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $estimatedDelivery = null;

    #[ORM\Column]
    private int $backerCount = 0;

    #[ORM\Column(nullable: true)]
    private ?int $backerLimit = null;

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getEstimatedDelivery(): ?\DateTimeInterface
    {
        return $this->estimatedDelivery;
    }

    public function setEstimatedDelivery(\DateTimeInterface $estimatedDelivery): static
    {
        $this->estimatedDelivery = $estimatedDelivery;
        return $this;
    }

    public function getBackerCount(): int
    {
        return $this->backerCount;
    }

    public function setBackerCount(int $backerCount): static
    {
        $this->backerCount = $backerCount;
        return $this;
    }

    public function getBackerLimit(): ?int
    {
        return $this->backerLimit;
    }

    public function setBackerLimit(?int $backerLimit): static
    {
        $this->backerLimit = $backerLimit;
        return $this;
    }

    public function hasReachedLimit(): bool
    {
        if ($this->backerLimit === null) {
            return false;
        }
        return $this->backerCount >= $this->backerLimit;
    }
}
