<?php

namespace App\Service\Payment;

use App\Entity\User;
use App\Entity\Donation;
use Psr\Log\LoggerInterface;
use App\Service\MatrixService;
use App\Service\DonationService;
use App\Service\MembershipService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractPaymentService implements PaymentServiceInterface
{
    protected EntityManagerInterface $em;
    protected MatrixService $matrixService;
    protected DonationService $donationService;
    protected LoggerInterface $logger;
    protected ParameterBagInterface $params;
    protected MembershipService $membershipService;

    public function __construct(
        EntityManagerInterface $em,
        MatrixService $matrixService,
        DonationService $donationService,
        LoggerInterface $logger,
        ParameterBagInterface $params,
        MembershipService $membershipService
    ) {
        $this->em = $em;
        $this->matrixService = $matrixService;
        $this->donationService = $donationService;
        $this->logger = $logger;
        $this->params = $params;
        $this->membershipService = $membershipService;
    }

    protected function processRegistrationPayment(Donation $donation, bool $includeMembership, string $paymentReference): void
    {
        try {
            $this->em->beginTransaction();

            // First update user status
            $donation->getDonor()
                ->setRegistrationPaymentStatus('completed')
                ->setIsKycVerified(false)
                ->setWaitingSince(null);

            // Now place user in matrix
            $this->matrixService->placeDonationInMatrix($donation);

            if (str_starts_with($paymentReference, 'pi_')) {
                $donation->setStripePaymentIntentId($paymentReference)
                    ->setPaymentProvider('stripe');
            } else {
                $donation->setCoinpaymentsTransactionId($paymentReference)
                    ->setPaymentProvider('coinpayments');
            }

            $donation->setPaymentStatus('completed');

            // Handle membership if included
            if ($includeMembership) {
                // Create membership donation
                $membershipDonation = $this->donationService->createMembershipDonation($donation->getDonor());
                
                if (str_starts_with($paymentReference, 'pi_')) {
                    $membershipDonation->setStripePaymentIntentId($paymentReference);
                    $membershipDonation->setPaymentProvider('stripe');
                } else {
                    $membershipDonation->setCoinpaymentsTransactionId($paymentReference);
                    $membershipDonation->setPaymentProvider('coinpayments');
                }
                
                $membershipDonation->setPaymentStatus('completed');
                
                // Create membership
                $this->membershipService->createMembership($donation->getDonor(), $membershipDonation);
            }

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

            // Process membership renewal
            $this->membershipService->renewMembership($user, $membershipDonation);

            $this->em->flush();
            $this->em->commit();

        } catch (\Exception $e) {
            $this->em->rollback();
            $this->logger->error('Failed to process membership payment: ' . $e->getMessage());
            throw $e;
        }
    }
}
