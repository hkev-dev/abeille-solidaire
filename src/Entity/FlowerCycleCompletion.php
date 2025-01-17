<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\FlowerCycleCompletionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlowerCycleCompletionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class FlowerCycleCompletion
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'flowerCycleCompletions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Flower::class, inversedBy: 'cycleCompletions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Flower $flower = null;

    #[ORM\Column(type: 'integer')]
    private int $cycleNumber = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $totalAmount = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $walletAmount = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $solidarityAmount = 0;

    #[ORM\Column(type: 'json')]
    private array $cyclePositions = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getFlower(): ?Flower
    {
        return $this->flower;
    }

    public function setFlower(?Flower $flower): self
    {
        $this->flower = $flower;
        return $this;
    }

    public function getCycleNumber(): int
    {
        return $this->cycleNumber;
    }

    public function setCycleNumber(int $cycleNumber): self
    {
        $this->cycleNumber = $cycleNumber;
        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(\DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        $this->calculateAmounts();
        return $this;
    }

    public function getWalletAmount(): float
    {
        return $this->walletAmount;
    }

    public function getSolidarityAmount(): float
    {
        return $this->solidarityAmount;
    }

    public function getCyclePositions(): array
    {
        return $this->cyclePositions;
    }

    public function setCyclePositions(array $positions): self
    {
        $this->cyclePositions = $positions;
        return $this;
    }

    private function calculateAmounts(): void
    {
        // 50% to wallet, 50% to solidarity
        $this->walletAmount = $this->totalAmount * 0.5;
        $this->solidarityAmount = $this->totalAmount * 0.5;
    }
}
