<?php

namespace App\Event;

use App\Entity\Membership;
use Symfony\Contracts\EventDispatcher\Event;

class MembershipExpiredEvent extends Event
{
    public const NAME = 'membership.expired';

    public function __construct(
        private readonly Membership $membership
    ) {
    }

    public function getMembership(): Membership
    {
        return $this->membership;
    }
}
