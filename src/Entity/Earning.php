<?php

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Repository\EarningRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EarningRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Earning
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $amount = null;

    #[ORM\ManyToOne(inversedBy: 'earnings')]
    private ?Donation $beneficiary = null;

    #[ORM\ManyToOne(inversedBy: 'earnings')]
    private ?Flower $flower = null;

    #[ORM\ManyToOne(inversedBy: 'beneficiaries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Donation $donor = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getBeneficiary(): ?Donation
    {
        return $this->beneficiary;
    }

    public function setBeneficiary(?Donation $beneficiary): static
    {
        $this->beneficiary = $beneficiary;

        return $this;
    }

    public function getFlower(): ?Flower
    {
        return $this->flower;
    }

    public function setFlower(?Flower $flower): static
    {
        $this->flower = $flower;

        return $this;
    }

    public function getDonor(): ?Donation
    {
        return $this->donor;
    }

    public function setDonor(?Donation $donor): static
    {
        $this->donor = $donor;

        return $this;
    }
}
