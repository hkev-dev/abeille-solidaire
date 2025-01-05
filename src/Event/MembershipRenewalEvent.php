<?php

namespace App\Event;

use App\Entity\User;
use App\Entity\Membership;
use Symfony\Contracts\EventDispatcher\Event;

class MembershipRenewalEvent extends Event
{
    public const PAYMENT_COMPLETED = 'membership.renewal.completed';
    public const PAYMENT_FAILED = 'membership.renewal.failed';

    public function __construct(
        private readonly User $user,
        private readonly ?Membership $membership = null,
        private readonly ?string $errorMessage = null
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMembership(): ?Membership
    {
        return $this->membership;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
