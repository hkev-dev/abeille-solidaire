<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\WithdrawalRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WithdrawalRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Withdrawal
{
    use TimestampableTrait;

    public const METHOD_STRIPE = 'stripe';
    public const METHOD_CRYPTO = 'crypto';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'withdrawals')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $amount;

    #[ORM\Column(length: 20)]
    private string $withdrawalMethod;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $requestedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $processedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureReason = null;

    public function __construct()
    {
        $this->requestedAt = new \DateTimeImmutable();
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

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getWithdrawalMethod(): string
    {
        return $this->withdrawalMethod;
    }

    public function setWithdrawalMethod(string $withdrawalMethod): self
    {
        if (!in_array($withdrawalMethod, [self::METHOD_STRIPE, self::METHOD_CRYPTO])) {
            throw new \InvalidArgumentException('Invalid withdrawal method');
        }
        $this->withdrawalMethod = $withdrawalMethod;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_PROCESSED, self::STATUS_FAILED])) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;
        return $this;
    }

    public function getRequestedAt(): \DateTimeInterface
    {
        return $this->requestedAt;
    }

    public function getProcessedAt(): ?\DateTimeInterface
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTimeInterface $processedAt): self
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): self
    {
        $this->failureReason = $failureReason;
        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessed(): bool
    {
        return $this->status === self::STATUS_PROCESSED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
