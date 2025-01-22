<?php

namespace App\Event;

use App\Entity\Donation;
use Symfony\Contracts\EventDispatcher\Event;

class DonationReceivedEvent extends Event
{
    public const NAME = 'donation.received';

    public function __construct(
        private readonly Donation $donation,
    ) {
    }

    public function getDonation(): Donation
    {
        return $this->donation;
    }
}
