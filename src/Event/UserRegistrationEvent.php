<?php

namespace App\Event;

use App\Entity\User;
use App\Entity\Donation;
use Symfony\Contracts\EventDispatcher\Event;

class UserRegistrationEvent extends Event
{
    public const NAME = 'user.registered';
    public const PAYMENT_COMPLETED = 'user.registration.payment_completed';
    public const PAYMENT_FAILED = 'user.registration.payment_failed';

    public function __construct(
        private readonly User $user,
        private readonly ?Donation $registrationDonation = null,
        private readonly ?string $paymentMethod = null,
        private readonly ?string $errorMessage = null
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRegistrationDonation(): ?Donation
    {
        return $this->registrationDonation;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
