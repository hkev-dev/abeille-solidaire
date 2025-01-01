<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\MembershipRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MembershipRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Membership
{
    use TimestampableTrait;

    public const ANNUAL_FEE = 25.00;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $endDate;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coinbaseChargeId = null;

    public function __construct()
    {
        $this->startDate = new \DateTimeImmutable();
        $this->endDate = $this->startDate->modify('+1 year');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        $this->endDate = (clone $startDate)->modify('+1 year');
        return $this;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function isActive(): bool
    {
        $now = new \DateTimeImmutable();
        return $this->startDate <= $now && $now <= $this->endDate;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $id): self
    {
        $this->stripePaymentIntentId = $id;
        return $this;
    }

    public function getCoinbaseChargeId(): ?string
    {
        return $this->coinbaseChargeId;
    }

    public function setCoinbaseChargeId(?string $id): self
    {
        $this->coinbaseChargeId = $id;
        return $this;
    }
}
