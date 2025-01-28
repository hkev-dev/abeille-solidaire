<?php

namespace App\Event;

use App\Entity\Membership;
use Symfony\Contracts\EventDispatcher\Event;

class MembershipActivatedEvent extends Event
{
    public const NAME = 'membership.activated';

    public function __construct(
        private readonly Membership $membership
    ) {
    }

    public function getMembership(): Membership
    {
        return $this->membership;
    }
}
