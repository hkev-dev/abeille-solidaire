<?php

namespace App\Service\Payment;

use App\Entity\Cause;
use App\Entity\Membership;
use App\Service\MembershipService;
use DateMalformedStringException;
use Exception;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use App\Entity\User;
use App\Entity\Donation;
use App\Entity\PonctualDonation;
use App\Service\CauseService;
use Stripe\PaymentIntent;
use App\Service\MatrixService;
use App\Service\DonationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Subscription;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StripePaymentService extends AbstractPaymentService
{
    const PAYMENT_PROVIDER = 'stripe';

    public function __construct(
        EntityManagerInterface $em,
        MatrixService          $matrixService,
        DonationService        $donationService,
        CauseService           $causeService,
        LoggerInterface        $logger,
        ParameterBagInterface  $params,
        MembershipService      $membershipService
    ) {
        parent::__construct($em, $matrixService, $donationService, $causeService, $logger, $params, $membershipService);
        Stripe::setApiKey($this->params->get('stripe.secret_key'));
    }

    public function createRegistrationPayment(User $user, bool $includeMembership): array
    {
        $amount = $includeMembership ? 5000 : 2500; // In cents (25€ or 50€)

        $donation = $this->donationService->createRegistrationDonation($user);

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'eur',
            'metadata' => [
                'donation_id' => $donation->getId(),
                'include_membership' => $includeMembership ? 'true' : 'false',
                'payment_type' => 'registration'
            ]
        ]);

        return [
            'entityId' => $donation->getId(),
            'clientSecret' => $paymentIntent->client_secret,
            'amount' => $amount / 100,
            'paymentIntentId' => $paymentIntent->id,
            'payment_reference' => $paymentIntent->id
        ];
    }

    public function createSupplementaryDonationPayment(User $user): array
    {
        $amount = DonationService::REGISTRATION_FEE * 100; // In cents (25€)

        $donation = $this->donationService->createSupplementaryDonation($user);

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'eur',
            'metadata' => [
                'donation_id' => $donation->getId(),
                'payment_type' => 'supplementary'
            ]
        ]);

        return [
            'entityId' => $donation->getId(),
            'clientSecret' => $paymentIntent->client_secret,
            'amount' => $amount / 100,
            'paymentIntentId' => $paymentIntent->id
        ];
    }


    public function createMembershipPayment(User $user): array
    {
        $amount = 2500; // 25€ in cents

        $membership = $this->membershipService->createMembership($user);

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'eur',
            'metadata' => [
                'membership_id' => $membership->getId(),
                'payment_type' => 'membership'
            ]
        ]);

        return [
            'entityId' => $membership->getId(),
            'clientSecret' => $paymentIntent->client_secret,
            'amount' => $amount / 100,
            'paymentIntentId' => $paymentIntent->id,
            'payment_reference' => $paymentIntent->id,
        ];
    }

    /**
     * @throws DateMalformedStringException
     * @throws ApiErrorException
     * @throws Exception
     */
    public function handlePaymentSuccess(array $paymentData): PayableInterface
    {
        $paymentIntent = PaymentIntent::retrieve($paymentData['payment_intent_id']);

        if ($paymentIntent->metadata['payment_type'] === self::PAYMENT_TYPE_MEMBERSHIP) {
            $payableObject = $membership = $this->em->getRepository(Membership::class)->find($paymentIntent->metadata['membership_id']);

            if (!$membership) {
                throw new Exception('Membership not found');
            }

            if ($membership->getPaymentStatus() === Donation::PAYMENT_COMPLETED){
                throw new Exception('Membership payment already processed');
            }

            $user = $membership->getUser();
            $this->processMembershipPayment($membership, $paymentIntent->id);
        } else if ($paymentIntent->metadata['payment_type'] === self::PAYMENT_TYPE_PDONATION) {
            $payableObject = $pDonation = $this->em->getRepository(PonctualDonation::class)->find($paymentIntent->metadata['donation_id']);

            if (!$pDonation) {
                throw new Exception('Donation not found');
            }

            if ($pDonation->isPaid()){
                throw new Exception('Donation payment already processed');
            }
            $this->processPDonationPayment($pDonation, true, $paymentIntent->id);
        }
        else{
            $payableObject = $donation = $this->em->getRepository(Donation::class)->find($paymentIntent->metadata['donation_id']);

            if (!$donation) {
                throw new Exception('Donation not found');
            }

            if ($donation->getPaymentStatus() === Donation::PAYMENT_COMPLETED){
                throw new Exception('Donation payment already processed');
            }

            $user = $donation->getDonor();
            $this->processPaymentType($donation, $paymentIntent->metadata['payment_type'], $paymentIntent->id, $paymentIntent->metadata['include_membership'] === 'true');
        }

        // Set Stripe customer ID if available
        if ($paymentIntent->customer && $user) {
            $user->setStripeCustomerId($paymentIntent->customer);
            $this->em->flush();
        }

        return $payableObject;
    }

    public function handlePaymentFailure(array $paymentData): void
    {
        $paymentIntent = PaymentIntent::retrieve($paymentData['payment_intent_id']);

        if ($paymentIntent->metadata['payment_type'] === self::PAYMENT_TYPE_PDONATION) {
            $pDonation = $this->em->getRepository(PonctualDonation::class)->find($paymentIntent->metadata['donation_id']);

            if (!$pDonation) {
                throw new Exception('Donation not found');
            }

            $this->processPDonationPayment($pDonation, false, $paymentIntent->id);
        } else {
            $paymentIntent = PaymentIntent::retrieve($paymentData['payment_intent_id']);
            $user = $this->em->getRepository(User::class)->find($paymentIntent->metadata['user_id']);
            
            if ($user && $user->getMainDonation()) {
                $user->getMainDonation()->setPaymentStatus('failed');
                $this->em->flush();
            }
        }
    }

    public function verifyPaymentCallback(array $data, string $signature): bool
    {
        return true; // No verification needed for Stripe
    }

    public static function getProvider(): string
    {
        return self::PAYMENT_PROVIDER;
    }

    public function createPonctualDonationPayment(?User $user, Cause $cause, string $donor, float $amount): array
    {
        $donation = $this->donationService->createPonctualDonation($user, $cause, $donor, $amount);

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount * 100,
            'currency' => 'eur',
            'metadata' => [
                'payment_type' => self::PAYMENT_TYPE_PDONATION,
                'donation_id' => $donation->getId()
            ]
        ]);

        return [
            'clientSecret' => $paymentIntent->client_secret,
            'amount' => $amount,
            'user' => $user ? $user->getId():null,
            'paymentIntentId' => $paymentIntent->id,
            'payment_reference' => $paymentIntent->id,
            'entityId' => $donation->getId()
        ];
    }

    /**
     * @throws DateMalformedStringException
     * @throws ApiErrorException
     * @throws Exception
     */
    public function handleSubscriptionSuccess(array $paymentData): PayableInterface
    {
        $subscription = Subscription::retrieve([
            'id' => $paymentData['subscription'],
            #'expand' => ['latest_invoice.payment_intent'],
        ]);

        $metadata = $subscription->metadata;

        $user = $this->em->getRepository(User::class)->find($metadata['user']);

        $this->logger->info("Data webhook", [
            'data' => $user->getEmail(),
        ]);


        if ($metadata['payment_type'] === self::PAYMENT_TYPE_SUPPLEMENTARY) {
            $payableObject = $donation = $this->donationService->createSupplementaryDonation($user);

            if (!$donation) {
                throw new Exception('Donation not found');
            }

            $donation->setSubscription(true);

            $this->processPaymentType($donation, $metadata['payment_type'], $subscription->id, false);
        }

        // Set Stripe customer ID if available
        if ($subscription->customer && $user) {
            $user->setStripeCustomerId($subscription->customer);
            $this->em->flush();
        }

        return $payableObject;
    }
}
