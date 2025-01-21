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

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';

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
    private ?string $coinpaymentsTxnId = null;  // Replace coinbaseChargeId

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $cryptoCurrency = null;  // Add cryptocurrency used

    #[ORM\Column(type: 'decimal', precision: 18, scale: 8, nullable: true)]
    private ?float $cryptoAmount = null;  // Add crypto amount

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $amount = self::ANNUAL_FEE;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

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

    public function getCoinpaymentsTxnId(): ?string
    {
        return $this->coinpaymentsTxnId;
    }

    public function setCoinpaymentsTxnId(?string $txnId): self
    {
        $this->coinpaymentsTxnId = $txnId;
        return $this;
    }

    public function getCryptoCurrency(): ?string
    {
        return $this->cryptoCurrency;
    }

    public function setCryptoCurrency(?string $currency): self
    {
        $this->cryptoCurrency = $currency;
        return $this;
    }

    public function getCryptoAmount(): ?float
    {
        return $this->cryptoAmount;
    }

    public function setCryptoAmount(?float $amount): self
    {
        $this->cryptoAmount = $amount;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function addMetadata(string $key, mixed $value): self
    {
        if ($this->metadata === null) {
            $this->metadata = [];
        }
        $this->metadata[$key] = $value;
        return $this;
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_ACTIVE, self::STATUS_EXPIRED])) {
            throw new \InvalidArgumentException('Invalid membership status');
        }
        $this->status = $status;
        return $this;
    }

    public function activate(): self
    {
        $this->status = self::STATUS_ACTIVE;
        return $this;
    }

    public function expire(): self
    {
        $this->status = self::STATUS_EXPIRED;
        return $this;
    }

    public function getRemainingDays(): int
    {
        if (!$this->isActive()) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        return max(0, $this->endDate->getTimestamp() - $now->getTimestamp()) / (24 * 3600);
    }

    public function isInGracePeriod(): bool
    {
        if ($this->isActive()) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $gracePeriodEnd = (clone $this->endDate)->modify('+30 days');
        return $now <= $gracePeriodEnd;
    }

    public function allowsMatrixProgression(): bool
    {
        return $this->isActive() || $this->isInGracePeriod();
    }

    public function allowsWithdrawal(): bool
    {
        // Must be active (not in grace period) for withdrawals
        return $this->isActive();
    }

    public function getDaysUntilGracePeriodEnds(): ?int
    {
        if (!$this->isInGracePeriod()) {
            return null;
        }

        $now = new \DateTimeImmutable();
        $gracePeriodEnd = (clone $this->endDate)->modify('+30 days');
        return max(0, $gracePeriodEnd->getTimestamp() - $now->getTimestamp()) / (24 * 3600);
    }
}
