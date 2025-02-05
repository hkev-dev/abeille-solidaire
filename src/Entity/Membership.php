<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\MembershipRepository;
use App\Service\Payment\PayableInterface;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use LogicException;

#[ORM\Entity(repositoryClass: MembershipRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Membership implements PayableInterface
{
    use TimestampableTrait;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'], inversedBy: 'memberships')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $endDate = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $activatedAt = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $paymentProvider = null;

    #[ORM\Column(length: 20)]
    private string $paymentStatus = 'pending';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paymentCompletedAt = null;

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

    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(DateTimeInterface $endDate): self
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
            throw new InvalidArgumentException('Invalid membership status');
        }
        $this->status = $status;
        return $this;
    }

    public function getActivatedAt(): ?DateTimeInterface
    {
        return $this->activatedAt;
    }

    public function activate(): void
    {
        if ($this->status === self::STATUS_ACTIVE) {
            throw new LogicException('Can only activate active memberships');
        }
        
        $this->status = self::STATUS_ACTIVE;
        $this->activatedAt = new DateTime();
        $this->startDate = new DateTime();
        $this->endDate = (new DateTime())->modify('+1 year');
    }

    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->endDate < new DateTime();
    }

    public function getDaysUntilExpiration(): ?int
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return null;
        }

        $now = new DateTime();
        $interval = $this->endDate->diff($now);

        if ($interval->invert === 0) {
            return 0; // Already expired
        }

        return $interval->days;
    }

    public function getPaymentProvider(): ?string
    {
        return $this->paymentProvider;
    }

    public function setPaymentProvider(?string $paymentProvider): self
    {
        $this->paymentProvider = $paymentProvider;
        return $this;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        if (!in_array($paymentStatus, ['pending', 'completed', 'failed'])) {
            throw new InvalidArgumentException('Invalid payment status');
        }
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateStatus(): void
    {
        if ($this->status === self::STATUS_ACTIVE && $this->isExpired()) {
            $this->status = self::STATUS_EXPIRED;
        }
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): static
    {
        $this->paymentReference = $paymentReference;

        return $this;
    }

    public function getPaymentCompletedAt(): ?DateTimeInterface
    {
        return $this->paymentCompletedAt;
    }

    public function setPaymentCompletedAt(?DateTimeInterface $paymentCompletedAt): static
    {
        $this->paymentCompletedAt = $paymentCompletedAt;

        return $this;
    }
}
