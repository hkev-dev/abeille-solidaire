<?php

namespace App\Event;

use App\Entity\Donation;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class DonationProcessedEvent extends Event
{
    public const NAME = 'donation.processed';

    public function __construct(
        private readonly Donation $donation
    ) {
    }

    public function getDonation(): Donation
    {
        return $this->donation;
    }

    public function getRecipient(): User
    {
        return $this->donation->getRecipient();
    }

    public function getDonor(): User
    {
        return $this->donation->getDonor();
    }

    public function getDonationType(): string
    {
        return $this->donation->getDonationType();
    }
}
