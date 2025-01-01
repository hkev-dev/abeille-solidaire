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
    public const TYPE_REFERRAL_PLACEMENT = 'referral_placement';
    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_SUPPLEMENTARY = 'supplementary';

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
    private ?string $coinbaseChargeId = null;

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

    public function getCoinbaseChargeId(): ?string
    {
        return $this->coinbaseChargeId;
    }

    public function setCoinbaseChargeId(?string $coinbaseChargeId): self
    {
        $this->coinbaseChargeId = $coinbaseChargeId;
        return $this;
    }

    public function getSolidarityDistributionStatus(): string
    {
        return $this->solidarityDistributionStatus;
    }

    public function setSolidarityDistributionStatus(string $status): self
    {
        if (!in_array($status, [
            self::SOLIDARITY_STATUS_PENDING,
            self::SOLIDARITY_STATUS_DISTRIBUTED,
            self::SOLIDARITY_STATUS_NOT_APPLICABLE
        ])) {
            throw new \InvalidArgumentException('Invalid solidarity distribution status');
        }
        $this->solidarityDistributionStatus = $status;
        return $this;
    }
}
