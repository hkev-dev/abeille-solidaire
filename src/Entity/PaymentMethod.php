<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\PaymentMethodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaymentMethodRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PaymentMethod
{
    use TimestampableTrait;

    public const TYPE_CARD = 'card';
    public const TYPE_RIB = 'rib';
    public const TYPE_CRYPTO = 'crypto';

    public const VALID_METHOD_TYPES = [
        self::TYPE_CARD,
        self::TYPE_RIB,
        self::TYPE_CRYPTO,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: self::VALID_METHOD_TYPES)]
    private string $methodType;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentMethodId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cryptoCurrency = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cryptoAddress = null;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastFour = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ribIban = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ribBic = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ribOwner = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cardBrand = null;

    #[ORM\ManyToOne(inversedBy: 'paymentMethods')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, Withdrawal>
     */
    #[ORM\OneToMany(targetEntity: Withdrawal::class, mappedBy: 'withdrawalMethod')]
    private Collection $withdrawals;

    public function __construct()
    {
        $this->withdrawals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMethodType(): string
    {
        return $this->methodType;
    }

    public function setMethodType(string $methodType): self
    {
        if (!in_array($methodType, self::VALID_METHOD_TYPES)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid payment method type. Must be one of: %s',
                implode(', ', self::VALID_METHOD_TYPES)
            ));
        }
        $this->methodType = $methodType;
        return $this;
    }

    public function getStripePaymentMethodId(): ?string
    {
        return $this->stripePaymentMethodId;
    }

    public function setStripePaymentMethodId(?string $stripePaymentMethodId): self
    {
        $this->stripePaymentMethodId = $stripePaymentMethodId;
        return $this;
    }

    public function getCryptoCurrency(): ?string
    {
        return $this->cryptoCurrency;
    }

    public function setCryptoCurrency(?string $cryptoCurrency): self
    {
        $this->cryptoCurrency = $cryptoCurrency;
        return $this;
    }

    public function getCryptoAddress(): ?string
    {
        return $this->cryptoAddress;
    }

    public function setCryptoAddress(?string $cryptoAddress): self
    {
        $this->cryptoAddress = $cryptoAddress;
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getLastFour(): ?string
    {
        return $this->lastFour;
    }

    public function setLastFour(?string $lastFour): self
    {
        $this->lastFour = $lastFour;
        return $this;
    }

    public function getCardBrand(): ?string
    {
        return $this->cardBrand;
    }

    public function setCardBrand(?string $cardBrand): self
    {
        $this->cardBrand = $cardBrand;
        return $this;
    }


    public function getRibIban(): ?string
    {
        return $this->ribIban;
    }

    public function setRibIban(?string $ribIban): self
    {
        $this->ribIban = $ribIban;
        return $this;
    }

    public function getRibBic(): ?string
    {
        return $this->ribBic;
    }

    public function setRibBic(?string $ribBic): self
    {
        $this->ribBic = $ribBic;
        return $this;
    }

    public function getRibOwner(): ?string
    {
        return $this->ribOwner;
    }

    public function setRibOwner(?string $ribOwner): self
    {
        $this->ribOwner = $ribOwner;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->getOwner();
    }

    public function setUser(?User $owner): self
    {
        $this->setOwner($owner);
        return $this;
    }

    /**
     * @return Collection<int, Withdrawal>
     */
    public function getWithdrawals(): Collection
    {
        return $this->withdrawals;
    }

    public function addWithdrawal(Withdrawal $withdrawal): static
    {
        if (!$this->withdrawals->contains($withdrawal)) {
            $this->withdrawals->add($withdrawal);
            $withdrawal->setWithdrawalMethod($this);
        }

        return $this;
    }

    public function removeWithdrawal(Withdrawal $withdrawal): static
    {
        if ($this->withdrawals->removeElement($withdrawal)) {
            // set the owning side to null (unless already changed)
            if ($withdrawal->getWithdrawalMethod() === $this) {
                $withdrawal->setWithdrawalMethod(null);
            }
        }

        return $this;
    }
}
