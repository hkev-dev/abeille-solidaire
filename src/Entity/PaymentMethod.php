<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\PaymentMethodRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentMethodRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PaymentMethod
{
    use TimestampableTrait;

    public const TYPE_CARD = 'card';
    public const TYPE_CRYPTO = 'crypto';

    public const VALID_METHOD_TYPES = [
        self::TYPE_CARD,
        self::TYPE_CRYPTO,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 20)]
    private string $methodType;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coinpaymentsTag = null;  // Replace coinbase_account_id with coinpaymentsTag

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cryptoCurrency = null;  // Add preferred cryptocurrency for CoinPayments

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastFour = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $cardBrand = null;

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

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): self
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

    public function getCoinpaymentsTag(): ?string 
    {
        return $this->coinpaymentsTag;
    }

    public function setCoinpaymentsTag(?string $tag): self
    {
        $this->coinpaymentsTag = $tag;
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
}
