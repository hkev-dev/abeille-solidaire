<?php

namespace App\Service\Payment;

use App\Entity\User;

interface PaymentServiceInterface
{
    /**
     * Create a payment for user registration (and optionally membership)
     */
    public function createRegistrationPayment(User $user, bool $includeMembership): array;

    /**
     * Handle successful payment callback
     */
    public function handlePaymentSuccess(array $paymentData): void;

    /**
     * Handle failed payment callback
     */
    public function handlePaymentFailure(array $paymentData): void;

    /**
     * Verify payment callback signature/authenticity
     */
    public function verifyPaymentCallback(array $data, string $signature): bool;
}