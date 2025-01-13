<?php

namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class KycVerificationEvent extends Event
{
    public const SUBMITTED = 'kyc.submitted';
    public const APPROVED = 'kyc.approved';
    public const REJECTED = 'kyc.rejected';

    public function __construct(
        private readonly User $user,
        private readonly string $status,
        private readonly ?string $reason = null
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }
}
