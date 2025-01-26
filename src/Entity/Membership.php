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

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';

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

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\OneToOne(targetEntity: Donation::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Donation $payment;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $activatedAt = null;

    public function __construct()
    {
        $this->startDate = new \DateTime();
        $this->endDate = (new \DateTime())->modify('+1 year');
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
        return $this;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_PENDING])) {
            throw new \InvalidArgumentException('Invalid membership status');
        }
        $this->status = $status;
        return $this;
    }

    public function getPayment(): Donation
    {
        return $this->payment;
    }

    public function setPayment(Donation $payment): self
    {
        if ($payment->getDonationType() !== Donation::TYPE_MEMBERSHIP) {
            throw new \InvalidArgumentException('Invalid donation type for membership payment');
        }
        $this->payment = $payment;
        return $this;
    }

    public function getActivatedAt(): ?\DateTimeInterface
    {
        return $this->activatedAt;
    }

    public function activate(): void
    {
        if ($this->status !== self::STATUS_PENDING) {
            throw new \LogicException('Can only activate pending memberships');
        }
        
        $this->status = self::STATUS_ACTIVE;
        $this->activatedAt = new \DateTime();
        $this->startDate = new \DateTime();
        $this->endDate = (new \DateTime())->modify('+1 year');
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->endDate < new \DateTime();
    }

    public function getDaysUntilExpiration(): ?int
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return null;
        }

        $now = new \DateTime();
        $interval = $this->endDate->diff($now);

        if ($interval->invert === 0) {
            return 0; // Already expired
        }

        return $interval->days;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateStatus(): void
    {
        if ($this->status === self::STATUS_ACTIVE && $this->isExpired()) {
            $this->status = self::STATUS_EXPIRED;
        }
    }

    public function renew(Donation $payment): void 
    {
        if ($payment->getDonationType() !== Donation::TYPE_MEMBERSHIP) {
            throw new \InvalidArgumentException('Invalid donation type for membership renewal');
        }

        $this->payment = $payment;
        $this->startDate = new \DateTime();
        $this->endDate = (clone $this->startDate)->modify('+1 year');
        $this->status = self::STATUS_ACTIVE;
        $this->activatedAt = new \DateTime();
    }
}
