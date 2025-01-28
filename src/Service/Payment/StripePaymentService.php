<?php

namespace App\Service\Payment;

use App\Service\MembershipService;
use Stripe\Stripe;
use App\Entity\User;
use App\Entity\Donation;
use Stripe\PaymentIntent;
use App\Service\MatrixService;
use App\Service\DonationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StripePaymentService extends AbstractPaymentService
{
    public function __construct(
        EntityManagerInterface $em,
        MatrixService $matrixService,
        DonationService $donationService,
        LoggerInterface $logger,
        ParameterBagInterface $params,
        MembershipService $membershipService
    ) {
        parent::__construct($em, $matrixService, $donationService, $logger, $params, $membershipService);
        Stripe::setApiKey($this->params->get('stripe.secret_key'));
    }

    public function createRegistrationPayment(User $user, bool $includeMembership): array
    {
        $amount = $includeMembership ? 5000 : 2500; // In cents (25€ or 50€)

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'eur',
            'metadata' => [
                'user_id' => $user->getId(),
                'include_membership' => $includeMembership ? 'true' : 'false',
                'payment_type' => 'registration'
            ]
        ]);

        return [
            'clientSecret' => $paymentIntent->client_secret,
            'amount' => $amount / 100,
            'paymentIntentId' => $paymentIntent->id
        ];
    }

    public function createMembershipPayment(User $user): array
    {
        $amount = 2500; // 25€ in cents

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'eur',
            'metadata' => [
                'user_id' => $user->getId(),
                'payment_type' => 'membership'
            ]
        ]);

        return [
            'clientSecret' => $paymentIntent->client_secret,
            'amount' => $amount / 100,
            'paymentIntentId' => $paymentIntent->id
        ];
    }

    public function handlePaymentSuccess(array $paymentData): void
    {
        $paymentIntent = PaymentIntent::retrieve($paymentData['payment_intent_id']);
        $user = $this->em->getRepository(User::class)->find($paymentIntent->metadata['user_id']);
        
        if (!$user) {
            throw new \Exception('User not found');
        }

        $paymentType = $paymentIntent->metadata['payment_type'];
        if ($paymentType === 'registration') {
            $includeMembership = $paymentIntent->metadata['include_membership'] === 'true';
            try {
                $this->em->beginTransaction();

                // First update payment status
                $user->setRegistrationPaymentStatus('completed')
                    ->setIsKycVerified(false)
                    ->setWaitingSince(null);
                $this->em->flush();

                // Then process the payment
                $this->processRegistrationPayment($user, $includeMembership, $paymentIntent->id);
                
                $this->em->commit();
            } catch (\Exception $e) {
                $this->em->rollback();
                throw $e;
            }
        } elseif ($paymentType === 'membership') {
            $this->processMembershipPayment($user, $paymentIntent->id);
        }

        // Set Stripe customer ID if available
        if ($paymentIntent->customer) {
            $user->setStripeCustomerId($paymentIntent->customer);
            $this->em->flush();
        }
    }

    public function handlePaymentFailure(array $paymentData): void
    {
        $paymentIntent = PaymentIntent::retrieve($paymentData['payment_intent_id']);
        $user = $this->em->getRepository(User::class)->find($paymentIntent->metadata['user_id']);
        
        if ($user) {
            $user->setRegistrationPaymentStatus('failed');
            $this->em->flush();
        }
    }

    public function verifyPaymentCallback(array $data, string $signature): bool
    {
        return true; // No verification needed for Stripe
    }
}
