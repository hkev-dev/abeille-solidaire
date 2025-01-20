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

    public const TYPE_DIRECT = 'direct';
    public const TYPE_SOLIDARITY = 'solidarity';
    public const TYPE_MATRIX_PROPAGATION = 'matrix_propagation';
    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_SUPPLEMENTARY = 'supplementary';
    public const TYPE_MEMBERSHIP = 'membership';

    public const SOLIDARITY_STATUS_PENDING = 'pending';
    public const SOLIDARITY_STATUS_DISTRIBUTED = 'distributed';
    public const SOLIDARITY_STATUS_NOT_APPLICABLE = 'not_applicable';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'donationsMade')]
    #[ORM\JoinColumn(nullable: false)]
    private User $donor;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'donationsReceived')]
    #[ORM\JoinColumn(nullable: false)]
    private User $recipient;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $amount;

    #[ORM\Column(length: 20)]
    private string $donationType;

    #[ORM\ManyToOne(targetEntity: Flower::class, inversedBy: 'donations')]
    #[ORM\JoinColumn(nullable: false)]
    private Flower $flower;

    #[ORM\Column(type: 'integer')]
    private int $cyclePosition;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $transactionDate;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coinpaymentsTransactionId = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $cryptoCurrency = null;

    #[ORM\Column(type: 'decimal', precision: 18, scale: 8, nullable: true)]
    private ?float $cryptoAmount = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6, nullable: true)]
    private ?float $exchangeRate = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $confirmationsNeeded = null;

    #[ORM\Column(type: 'smallint', nullable: true)]
    private ?int $confirmationsReceived = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statusUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $destinationAddress = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $cryptoStatus = null;

    #[ORM\Column(length: 20)]
    private string $solidarityDistributionStatus = self::SOLIDARITY_STATUS_NOT_APPLICABLE;

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

    public function getDonationType(): string
    {
        return $this->donationType;
    }

    public function setDonationType(string $donationType): self
    {
        $this->donationType = $donationType;
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

    public function getCyclePosition(): int
    {
        return $this->cyclePosition;
    }

    public function setCyclePosition(int $cyclePosition): self
    {
        $this->cyclePosition = $cyclePosition;
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

    public function setCoinpaymentsTransactionId(?string $txnId): self
    {
        $this->coinpaymentsTransactionId = $txnId;
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

    public function getExchangeRate(): ?float
    {
        return $this->exchangeRate;
    }

    public function setExchangeRate(?float $rate): self
    {
        $this->exchangeRate = $rate;
        return $this;
    }

    public function getConfirmationsNeeded(): ?int
    {
        return $this->confirmationsNeeded;
    }

    public function setConfirmationsNeeded(?int $confirms): self
    {
        $this->confirmationsNeeded = $confirms;
        return $this;
    }

    public function getConfirmationsReceived(): ?int
    {
        return $this->confirmationsReceived;
    }

    public function setConfirmationsReceived(?int $confirms): self
    {
        $this->confirmationsReceived = $confirms;
        return $this;
    }

    public function getStatusUrl(): ?string
    {
        return $this->statusUrl;
    }

    public function setStatusUrl(?string $url): self
    {
        $this->statusUrl = $url;
        return $this;
    }

    public function getDestinationAddress(): ?string
    {
        return $this->destinationAddress;
    }

    public function setDestinationAddress(?string $address): self
    {
        $this->destinationAddress = $address;
        return $this;
    }

    public function getCryptoStatus(): ?string
    {
        return $this->cryptoStatus;
    }

    public function setCryptoStatus(?string $status): self
    {
        $this->cryptoStatus = $status;
        return $this;
    }

    public function getSolidarityDistributionStatus(): string
    {
        return $this->solidarityDistributionStatus;
    }

    public function setSolidarityDistributionStatus(string $status): self
    {
        if (
            !in_array($status, [
                self::SOLIDARITY_STATUS_PENDING,
                self::SOLIDARITY_STATUS_DISTRIBUTED,
                self::SOLIDARITY_STATUS_NOT_APPLICABLE
            ])
        ) {
            throw new \InvalidArgumentException('Invalid solidarity distribution status');
        }
        $this->solidarityDistributionStatus = $status;
        return $this;
    }

    public function validateMatrixDonation(): bool
    {
        // Validate cycle position for matrix-based donations
        if (in_array($this->donationType, ['direct', 'matrix_propagation', 'registration'])) {
            if ($this->cyclePosition < 1 || $this->cyclePosition > 4) {
                return false;
            }

            // For matrix propagation, ensure donor is parent of recipient
            if ($this->donationType === 'matrix_propagation' && $this->recipient->getParent() !== $this->donor) {
                return false;
            }
        }

        return true;
    }

    public function isMatrixRelated(): bool
    {
        return in_array($this->donationType, [
            self::TYPE_DIRECT,
            self::TYPE_MATRIX_PROPAGATION,
            self::TYPE_REGISTRATION
        ]);
    }

    public function getCycleType(): string
    {
        if ($this->isMatrixRelated()) {
            return 'matrix';
        } elseif ($this->donationType === self::TYPE_SOLIDARITY) {
            return 'solidarity';
        } else {
            return 'other';
        }
    }
}
