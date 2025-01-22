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

    #[ORM\ManyToOne(targetEntity: Flower::class, inversedBy: 'donations')]
    #[ORM\JoinColumn(nullable: false)]
    private Flower $flower;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $transactionDate;

    #[ORM\Column(type: 'string', length: 20)]
    private string $donationType;

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
        ])) {
            throw new \InvalidArgumentException('Invalid donation type');
        }

        $this->donationType = $donationType;
        return $this;
    }
}
