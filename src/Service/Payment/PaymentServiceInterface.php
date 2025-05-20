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
     * Create a payment for user registration (and optionally membership)
     */
    public function createSupplementaryDonationPayment(User $user): array;

    /**
     * Handle successful payment callback
     */
    public function handlePaymentSuccess(array $paymentData): PayableInterface;
    public function handleSubscriptionSuccess(array $paymentData);

    /**
     * Handle failed payment callback
     */
    public function handlePaymentFailure(array $paymentData): void;

    /**
     * Verify payment callback signature/authenticity
     */
    public function verifyPaymentCallback(array $data, string $signature): bool;

    /**
     * Create a payment for membership renewal
     */
    public function createMembershipPayment(User $user): array;

    public static function getProvider(): string;

}