<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\FlowerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FlowerRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Flower
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private float $donationAmount;

    #[ORM\Column(type: 'integer')]
    private int $level;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'currentFlower')]
    private Collection $currentUsers;

    #[ORM\OneToMany(targetEntity: Donation::class, mappedBy: 'flower')]
    private Collection $donations;

    public function __construct()
    {
        $this->currentUsers = new ArrayCollection();
        $this->donations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDonationAmount(): float
    {
        return $this->donationAmount;
    }

    public function setDonationAmount(float $donationAmount): self
    {
        $this->donationAmount = $donationAmount;
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;
        return $this;
    }

    public function getCurrentUsers(): Collection
    {
        return $this->currentUsers;
    }

    public function getDonations(): Collection
    {
        return $this->donations;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
