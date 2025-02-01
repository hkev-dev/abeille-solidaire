<?php

namespace App\Event;

use App\Entity\Donation;
use Symfony\Contracts\EventDispatcher\Event;

class DonationPositionedInMatrixEvent extends Event
{
    public const NAME = 'donation.positioned.in.matrix';

    public function __construct(private readonly Donation $donation)
    {
    }

    public function getDonation(): Donation
    {
        return $this->donation;
    }
}
