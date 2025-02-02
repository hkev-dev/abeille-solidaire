<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\DonationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: DonationRepository::class)]
#[UniqueEntity(fields: ['matrixPosition'], message: 'Matrix position is already taken')]
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
    public const PAYMENT_SHARE = 0.5; // 50%
    public const PAYMENT_COMPLETED = "completed";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'donationsMade', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private User $donor;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'donationsReceived')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $recipient = null;

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

    #[ORM\Column(type: 'integer', unique: true, nullable: true)]
    private ?int $matrixPosition = null;

    #[ORM\Column(type: 'integer')]
    private int $matrixDepth = 0;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'childrens')]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $childrens;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paymentCompletedAt = null;

    /**
     * @var Collection<int, Earning>
     */
    #[ORM\OneToMany(targetEntity: Earning::class, mappedBy: 'beneficiary', cascade: ['persist'])]
    private Collection $earnings;

    /**
     * @var Collection<int, Earning>
     */
    #[ORM\OneToMany(targetEntity: Earning::class, mappedBy: 'donor')]
    private Collection $beneficiaries;

    public function __construct()
    {
        $this->childrens = new ArrayCollection();
        $this->earnings = new ArrayCollection();
        $this->beneficiaries = new ArrayCollection();
    }

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

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient = null): self
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

    public function getMatrixPosition(): ?int
    {
        return $this->matrixPosition;
    }

    public function setMatrixPosition(?int $position): self
    {
        if ($position !== null && $position < 0) {
            throw new \InvalidArgumentException('Matrix position cannot be negative');
        }

        $this->matrixPosition = $position;
        return $this;
    }

    public function getMatrixDepth(): int
    {
        return $this->matrixDepth;
    }

    public function setMatrixDepth(int $depth): self
    {
        if ($depth < 0) {
            throw new \InvalidArgumentException('Matrix depth cannot be negative');
        }
        $this->matrixDepth = $depth;
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function getChildrens(): Collection
    {
        return $this->childrens;
    }

    public function hasAvailableMatrixSlot(): bool
    {
        return $this->childrens->count() < 4;
    }

    public function getMatrixLevel(): int
    {
        return $this->matrixDepth + 1;
    }

    public function getMatrixChildrenCount(): int
    {
        return $this->childrens->count();
    }

    /**
     * Get a human-readable label for the donation type
     */
    public function getTypeLabel(): string
    {
        return match ($this->donationType) {
            self::TYPE_REGISTRATION => "Inscription",
            self::TYPE_SOLIDARITY => "Solidarité",
            self::TYPE_SUPPLEMENTARY => "Supplémentaire",
            self::TYPE_MEMBERSHIP => "Adhésion",
            default => "Inconnu"
        };
    }

    public function getCurrentFlower(): Flower
    {
        return $this->getFlower();
    }

    public function getPaymentCompletedAt(): ?\DateTimeInterface
    {
        return $this->paymentCompletedAt;
    }

    public function setPaymentCompletedAt(?\DateTimeInterface $paymentCompletedAt): static
    {
        $this->paymentCompletedAt = $paymentCompletedAt;

        return $this;
    }

    public function canLevelUp(): bool
    {
        if ($this->getChildrens()->count() < 4){
            return false;
        }

        // Check if all children are completed
        /** @var Donation $child */
        foreach ($this->getChildrens() as $child) {
            if ($child->getFlower()->getLevel() < $this->getFlower()->getLevel()) {
                return false;
            }
        }

        return true;
    }

    public function countAllChildrens(): int
    {
        $childrensCount = 0;

        foreach ($this->getChildrens() as $child) {
            if ($child->getPaymentStatus() === Donation::PAYMENT_COMPLETED) {
                $childrensCount++;
                $childrensCount += $child->countAllChildrens();
            }
        }

        return $childrensCount;
    }

    /**
     * @return Collection<int, Earning>
     */
    public function getEarnings(): Collection
    {
        return $this->earnings;
    }

    public function addEarning(Earning $earning): static
    {
        if (!$this->earnings->contains($earning)) {
            $this->earnings->add($earning);
            $earning->setBeneficiary($this);
        }

        return $this;
    }

    public function removeEarning(Earning $earning): static
    {
        if ($this->earnings->removeElement($earning)) {
            // set the owning side to null (unless already changed)
            if ($earning->getBeneficiary() === $this) {
                $earning->setBeneficiary(null);
            }
        }

        return $this;
    }

    public function getEarningsAmount(): float
    {
        return array_reduce($this->getEarnings()->toArray(), function($carry, $item) {
            return $carry + $item->getAmount();
        }, 0.0);
    }

    /**
     * @return Collection<int, Earning>
     */
    public function getBeneficiaries(): Collection
    {
        return $this->beneficiaries;
    }

    public function addBeneficiary(Earning $givedTo): static
    {
        if (!$this->beneficiaries->contains($givedTo)) {
            $this->beneficiaries->add($givedTo);
            $givedTo->setDonor($this);
        }

        return $this;
    }

    public function removeBeneficiary(Earning $givedTo): static
    {
        if ($this->beneficiaries->removeElement($givedTo)) {
            // set the owning side to null (unless already changed)
            if ($givedTo->getDonor() === $this) {
                $givedTo->setDonor(null);
            }
        }

        return $this;
    }

    public function getBeneficiariesName(): string
    {
        $otherParties = $this->getBeneficiaries()->map(function(Earning $earning) {
            return $earning->getBeneficiary()->getDonor()->getFullName();
        })->toArray();

        return implode(', ', $otherParties);
    }
}
