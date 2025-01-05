<?php

namespace App\Event;

use App\Entity\User;
use App\Entity\Flower;
use Symfony\Contracts\EventDispatcher\Event;

class FlowerCycleCompletedEvent extends Event
{
    public function __construct(
        private readonly User $user,
        private readonly Flower $completedFlower,
        private readonly ?Flower $nextFlower,
        private readonly float $walletAmount
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCompletedFlower(): Flower
    {
        return $this->completedFlower;
    }

    public function getNextFlower(): ?Flower
    {
        return $this->nextFlower;
    }

    public function getWalletAmount(): float
    {
        return $this->walletAmount;
    }
}
