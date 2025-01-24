<?php

namespace App\Service\Payment;

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
        ParameterBagInterface $params
    ) {
        parent::__construct($em, $matrixService, $donationService, $logger, $params);
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
            $this->processRegistrationPayment($user, $includeMembership, $paymentIntent->id);
        } elseif ($paymentType === 'membership') {
            $this->processMembershipPayment( $user, $paymentIntent->id);
        }

        // Set Stripe customer ID
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

    protected function processRegistrationPayment(User $user, bool $includeMembership, string $paymentIntentId): void
    {
        try {
            $this->em->beginTransaction();

            // Place user in matrix and create registration donation
            $this->matrixService->placeUserInMatrix($user);
            
            // Get the parent's donation and update payment info
            $registrationDonation = $this->em->getRepository(Donation::class)
                ->findOneBy([
                    'donor' => $user,
                    'donationType' => Donation::TYPE_REGISTRATION
                ]);
            
            if ($registrationDonation) {
                $registrationDonation
                    ->setStripePaymentIntentId($paymentIntentId)
                    ->setPaymentProvider('stripe')
                    ->setPaymentStatus('completed');
            }

            // Handle membership if included
            if ($includeMembership) {
                $user->setHasPaidAnnualFee(true);
                
                // Create membership donation with payment info
                $membershipDonation = $this->donationService->createMembershipDonation($user);
                $membershipDonation
                    ->setStripePaymentIntentId($paymentIntentId)
                    ->setPaymentProvider('stripe')
                    ->setPaymentStatus('completed');
            }

            // Update user status
            $user->setRegistrationPaymentStatus('completed')
                ->setIsKycVerified(false)
                ->setWaitingSince(null);

            $this->em->flush();
            $this->em->commit();

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Failed to process Stripe registration payment: ' . $e->getMessage());
            throw $e;
        }
    }
}