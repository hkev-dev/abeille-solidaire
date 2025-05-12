<?php

namespace App\Entity;

use App\Repository\PonctualDonationRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Trait\TimestampableTrait;

#[ORM\Entity(repositoryClass: PonctualDonationRepository::class)]
class PonctualDonation
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'amount')]
    private ?Cause $cause = null;

    #[ORM\Column]
    private ?float $amount = null;

    #[ORM\Column(length: 255)]
    private ?string $donorName = null;

    #[ORM\Column]
    private ?bool $isAnonymous = null;

    #[ORM\Column(nullable: true)]
    private ?int $_user_id = null;

    #[ORM\Column]
    private ?bool $isPaid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ref = null;

    public function __construct()
    {
        $this->setCreatedAtValue();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCause(): ?Cause
    {
        return $this->cause;
    }

    public function setCause(?Cause $cause): static
    {
        $this->cause = $cause;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getDonor(): ?string
    {
        return $this->donorName;
    }

    public function setDonor(string $donorName): static
    {
        $this->donorName = $donorName;

        return $this;
    }

    public function isAnonymous(): ?bool
    {
        return $this->isAnonymous;
    }

    public function setAnonymous(bool $isAnonymous): static
    {
        $this->isAnonymous = $isAnonymous;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->_user_id;
    }

    public function setUserId(?int $_user_id): static
    {
        $this->_user_id = $_user_id;

        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->isPaid;
    }

    public function setPaid(bool $isPaid): static
    {
        $this->isPaid = $isPaid;

        return $this;
    }

    public function getRef(): ?string
    {
        return $this->ref;
    }

    public function setRef(?string $ref): static
    {
        $this->ref = $ref;

        return $this;
    }
}
