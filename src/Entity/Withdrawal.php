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
    public const METHOD_RIB = 'rib';
    public const METHOD_CRYPTO = 'crypto';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED = 'failed';

    public const FEE_PERCENTAGE = 0.06; // 6%
    public const MIN_AMOUNT = 50.00;
    public const MAX_AMOUNT = 10000.00;

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
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $requestedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $processedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $failureReason = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $feeAmount;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coinpaymentsWithdrawalId = null;  // Add CoinPayments withdrawal ID

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cryptoAddress = null;  // Add crypto address for withdrawal

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $cryptoCurrency = null;  // Add cryptocurrency for withdrawal

    #[ORM\Column(type: 'decimal', precision: 18, scale: 8, nullable: true)]
    private ?float $cryptoAmount = null;

    #[ORM\ManyToOne(inversedBy: 'withdrawals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PaymentMethod $withdrawalMethod = null;  // Add crypto amount for withdrawal

    public function __construct()
    {
        $this->requestedAt = new \DateTimeImmutable();
        $this->feeAmount = 0.0;
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
        if ($amount < self::MIN_AMOUNT) {
            throw new \InvalidArgumentException(sprintf(
                'Withdrawal amount must be at least %.2f€',
                self::MIN_AMOUNT
            ));
        }
        if ($amount > self::MAX_AMOUNT) {
            throw new \InvalidArgumentException(sprintf(
                'Withdrawal amount cannot exceed %.2f€',
                self::MAX_AMOUNT
            ));
        }
        $this->amount = $amount;
        $this->calculateFee();
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

    public function getFeeAmount(): float
    {
        return $this->feeAmount;
    }

    public function validateMatrixRequirements(): bool
    {
        // Check if user meets matrix requirements
        if (!$this->user->getCurrentMembership()?->allowsWithdrawal()) {
            $this->failureReason = 'Active membership required for withdrawals';
            return false;
        }

        // Check matrix depth requirement (minimum 4 levels)
        if ($this->user->getMatrixDepth() < 3) {
            $this->failureReason = 'Matrix depth requirement not met (minimum 4 levels needed)';
            return false;
        }

        // Check if user has a project description
        if (!$this->user->getProjectDescription()) {
            $this->failureReason = 'Project description required for withdrawals';
            return false;
        }

        return true;
    }

    public function validate(): bool
    {
        if (!$this->validateMatrixRequirements()) {
            return false;
        }

        // Validate amount limits
        if ($this->amount < self::MIN_AMOUNT || $this->amount > self::MAX_AMOUNT) {
            $this->failureReason = sprintf(
                'Amount must be between %.2f€ and %.2f€',
                self::MIN_AMOUNT,
                self::MAX_AMOUNT
            );
            return false;
        }

        // Validate user has sufficient balance
        if ($this->user->getWalletBalance() < $this->amount) {
            $this->failureReason = 'Insufficient wallet balance';
            return false;
        }

        return true;
    }

    public function calculateFee(): void
    {
        $this->feeAmount = round($this->amount * self::FEE_PERCENTAGE, 2);
    }

    public function getNetAmount(): float
    {
        return $this->amount - $this->feeAmount;
    }

    public function getCoinpaymentsWithdrawalId(): ?string
    {
        return $this->coinpaymentsWithdrawalId;
    }

    public function setCoinpaymentsWithdrawalId(?string $id): self
    {
        $this->coinpaymentsWithdrawalId = $id;
        return $this;
    }

    public function getCryptoAddress(): ?string
    {
        return $this->cryptoAddress;
    }

    public function setCryptoAddress(?string $address): self
    {
        $this->cryptoAddress = $address;
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

//    public function withdrawalMethodInfo(): ?PaymentMethod
//    {
//        return $this->withdrawalMethodInfo ?? $this->getUser()->getWithdrawalMethod($this->getWithdrawalMethod());
//    }

public function getWithdrawalMethod(): ?PaymentMethod
{
    return $this->withdrawalMethod;
}

public function setWithdrawalMethod(?PaymentMethod $withdrawalMethod): static
{
    $this->withdrawalMethod = $withdrawalMethod;

    return $this;
}
}
