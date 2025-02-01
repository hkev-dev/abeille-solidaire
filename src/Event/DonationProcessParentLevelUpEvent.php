<?php

namespace App\Event;

use App\Entity\Donation;
use Symfony\Contracts\EventDispatcher\Event;

class DonationProcessParentLevelUpEvent extends Event
{
    public const NAME = 'donation.process.parent.level.up';

    public function __construct(private readonly Donation $donation)
    {
    }

    public function getDonation(): Donation
    {
        return $this->donation;
    }
}
