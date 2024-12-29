<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\ProjectBackingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectBackingRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ProjectBacking
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'backings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne(inversedBy: 'backedProjects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $backer = null;

    #[ORM\Column]
    private float $amount = 0.0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private bool $isAnonymous = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getBacker(): ?User
    {
        return $this->backer;
    }

    public function setBacker(?User $backer): static
    {
        $this->backer = $backer;
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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }

    public function isAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    public function setIsAnonymous(bool $isAnonymous): static
    {
        $this->isAnonymous = $isAnonymous;
        return $this;
    }
}
