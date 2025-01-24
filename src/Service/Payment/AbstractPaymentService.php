<?php

namespace App\Service\Payment;

use App\Entity\User;
use App\Service\MatrixService;
use App\Service\DonationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractPaymentService implements PaymentServiceInterface
{
    protected EntityManagerInterface $em;
    protected MatrixService $matrixService;
    protected DonationService $donationService;
    protected LoggerInterface $logger;
    protected ParameterBagInterface $params;

    public function __construct(
        EntityManagerInterface $em,
        MatrixService $matrixService,
        DonationService $donationService,
        LoggerInterface $logger,
        ParameterBagInterface $params
    ) {
        $this->em = $em;
        $this->matrixService = $matrixService;
        $this->donationService = $donationService;
        $this->logger = $logger;
        $this->params = $params;
    }

    protected function processRegistrationPayment(User $user, bool $includeMembership, string $paymentReference): void
    {
        try {
            $this->em->beginTransaction();

            // Place user in matrix
            $this->matrixService->placeUserInMatrix($user);

            // Handle membership if included
            if ($includeMembership) {
                $user->setHasPaidAnnualFee(true);
                
                // Create membership donation to root user
                $this->donationService->createMembershipDonation($user);
            }

            // Update user status
            $user->setRegistrationPaymentStatus('completed')
                ->setIsKycVerified(false) // Requires manual verification
                ->setWaitingSince(null);   // No longer waiting

            $this->em->flush();
            $this->em->commit();

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Failed to process registration payment: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processMembershipPayment(User $user, string $paymentReference): void
    {
        try {
            $this->em->beginTransaction();

            // Update user membership status
            $user->setHasPaidAnnualFee(true);

            // Create membership donation record
            $membershipDonation = $this->donationService->createMembershipDonation($user);
            
            if (str_starts_with($paymentReference, 'pi_')) {
                $membershipDonation->setStripePaymentIntentId($paymentReference);
                $membershipDonation->setPaymentProvider('stripe');
            } else {
                $membershipDonation->setCoinpaymentsTransactionId($paymentReference);
                $membershipDonation->setPaymentProvider('coinpayments');
            }
            
            $membershipDonation->setPaymentStatus('completed');

            $this->em->flush();
            $this->em->commit();

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Failed to process membership payment: ' . $e->getMessage());
            throw $e;
        }
    }
}