<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class DonationProcessedEvent extends Event
{
    public const NAME = 'donation.processed';

    public function __construct(
        private readonly User $recipient,
        private readonly User $donor,
        private readonly string $donationType
    ) {
    }

    public function getRecipient(): User
    {
        return $this->recipient;
    }

    public function getDonor(): User
    {
        return $this->donor;
    }

    public function getDonationType(): string
    {
        return $this->donationType;
    }
}
