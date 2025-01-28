<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\DonationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DonationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Donation
{
    use TimestampableTrait;

    public const TYPE_SOLIDARITY = 'solidarity';
    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_SUPPLEMENTARY = 'supplementary';
    public const TYPE_MEMBERSHIP = 'membership';

    public const PROVIDER_STRIPE = 'stripe';
    public const PROVIDER_COINPAYMENTS = 'coinpayments';
    public const PROVIDER_INTERNAL = 'internal';

    public const PAYMENT_PROVIDERS = [
        self::PROVIDER_STRIPE,
        self::PROVIDER_COINPAYMENTS,
        self::PROVIDER_INTERNAL
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'donationsMade', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private User $donor;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'donationsReceived')]
    #[ORM\JoinColumn(nullable: false)]
    private User $recipient;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $amount;

    #[ORM\ManyToOne(targetEntity: Flower::class, inversedBy: 'donations', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private Flower $flower;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $transactionDate;

    #[ORM\Column(type: 'string', length: 20)]
    private string $donationType;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coinpaymentsTransactionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cryptoWithdrawalTransactionId = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $paymentProvider = null;

    #[ORM\Column(length: 20)]
    private string $paymentStatus = 'pending';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDonor(): User
    {
        return $this->donor;
    }

    public function setDonor(User $donor): self
    {
        $this->donor = $donor;
        return $this;
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function setRecipient(User $recipient): self
    {
        $this->recipient = $recipient;
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

    public function getFlower(): Flower
    {
        return $this->flower;
    }

    public function setFlower(Flower $flower): self
    {
        $this->flower = $flower;
        return $this;
    }

    public function getTransactionDate(): \DateTimeInterface
    {
        return $this->transactionDate;
    }

    public function setTransactionDate(\DateTimeInterface $transactionDate): self
    {
        $this->transactionDate = $transactionDate;
        return $this;
    }

    public function getDonationType(): string
    {
        return $this->donationType;
    }

    public function setDonationType(string $donationType): self
    {
        if (!in_array($donationType, [
            self::TYPE_SOLIDARITY,
            self::TYPE_REGISTRATION,
            self::TYPE_SUPPLEMENTARY,
            self::TYPE_MEMBERSHIP
        ])) {
            throw new \InvalidArgumentException('Invalid donation type');
        }

        $this->donationType = $donationType;
        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        return $this;
    }

    public function getCoinpaymentsTransactionId(): ?string
    {
        return $this->coinpaymentsTransactionId;
    }

    public function setCoinpaymentsTransactionId(?string $coinpaymentsTransactionId): self
    {
        $this->coinpaymentsTransactionId = $coinpaymentsTransactionId;
        return $this;
    }

    public function getCryptoWithdrawalTransactionId(): ?string
    {
        return $this->cryptoWithdrawalTransactionId;
    }

    public function setCryptoWithdrawalTransactionId(?string $cryptoWithdrawalTransactionId): self
    {
        $this->cryptoWithdrawalTransactionId = $cryptoWithdrawalTransactionId;
        return $this;
    }

    public function getPaymentProvider(): ?string
    {
        return $this->paymentProvider;
    }

    public function setPaymentProvider(?string $paymentProvider): self
    {
        if ($paymentProvider !== null && !in_array($paymentProvider, self::PAYMENT_PROVIDERS)) {
            throw new \InvalidArgumentException('Invalid payment provider');
        }
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
            throw new \InvalidArgumentException('Invalid payment status');
        }
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    /**
     * Helper method for templates to get donation type
     */
    public function getType(): string
    {
        return $this->donationType;
    }

    /**
     * Get a human-readable label for the donation type
     */
    public function getTypeLabel(): string
    {
        return match($this->donationType) {
            self::TYPE_REGISTRATION => "Inscription",
            self::TYPE_SOLIDARITY => "Solidarité",
            self::TYPE_SUPPLEMENTARY => "Supplémentaire",
            self::TYPE_MEMBERSHIP => "Adhésion",
            default => "Inconnu"
        };
    }
}
