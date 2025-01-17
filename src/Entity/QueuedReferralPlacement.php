<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QueuedReferralPlacementRepository::class)]
#[ORM\HasLifecycleCallbacks]
class QueuedReferralPlacement
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $referral = null;

    #[ORM\ManyToOne(targetEntity: Flower::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Flower $flower = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $queuedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReferral(): ?User
    {
        return $this->referral;
    }

    public function setReferral(?User $referral): self
    {
        $this->referral = $referral;
        return $this;
    }

    public function getFlower(): ?Flower 
    {
        return $this->flower;
    }

    public function setFlower(?Flower $flower): self
    {
        $this->flower = $flower;
        return $this;
    }

    public function getQueuedAt(): ?\DateTimeImmutable
    {
        return $this->queuedAt;
    }

    public function setQueuedAt(\DateTimeImmutable $queuedAt): self
    {
        $this->queuedAt = $queuedAt;
        return $this;
    }
}
