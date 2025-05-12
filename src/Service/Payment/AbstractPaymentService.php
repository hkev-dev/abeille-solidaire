<?php

namespace App\Service\Payment;

use App\Entity\Membership;
use App\Entity\User;
use App\Entity\Donation;
use App\Entity\PonctualDonation;
use App\Service\ObjectService;
use DateMalformedStringException;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use App\Service\MatrixService;
use App\Service\DonationService;
use App\Service\MembershipService;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractPaymentService implements PaymentServiceInterface
{
    const PAYMENT_TYPE_REGISTRATION = Donation::TYPE_REGISTRATION;
    const PAYMENT_TYPE_MEMBERSHIP = 'membership';
    const PAYMENT_TYPE_PDONATION = 'payment';
    const PAYMENT_TYPE_SUPPLEMENTARY = Donation::TYPE_SUPPLEMENTARY;

    protected EntityManagerInterface $em;
    protected MatrixService $matrixService;
    protected DonationService $donationService;
    protected LoggerInterface $logger;
    protected ParameterBagInterface $params;
    protected MembershipService $membershipService;

    public function __construct(
        EntityManagerInterface $em,
        MatrixService          $matrixService,
        DonationService        $donationService,
        LoggerInterface        $logger,
        ParameterBagInterface  $params,
        MembershipService      $membershipService
    )
    {
        $this->em = $em;
        $this->matrixService = $matrixService;
        $this->donationService = $donationService;
        $this->logger = $logger;
        $this->params = $params;
        $this->membershipService = $membershipService;
    }

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    protected function processPaymentType(Donation $donation, string $paymentType, string $paymentReference, ?bool $includeMembership = false): void
    {
        if ($paymentType === self::PAYMENT_TYPE_REGISTRATION) {
            $this->processDonationPayment($donation, $paymentReference);

            if ($includeMembership){
                $membership = $this->membershipService->createMembership($donation->getDonor());
                $this->processMembershipPayment($membership, $paymentReference);
            }
        } elseif ($paymentType === self::PAYMENT_TYPE_SUPPLEMENTARY) {
            $this->processDonationPayment($donation, $paymentReference);
        }
    }

    /**
     * @throws DateMalformedStringException
     * @throws \ReflectionException
     */
    protected function processDonationPayment(Donation $donation, string $paymentReference): void
    {
        $callerClass = ObjectService::getCallerClass(PaymentServiceInterface::class);

        if (!$callerClass) {
            throw new RuntimeException('Donation Payment processing can only be called from a PaymentServiceInterface implementation');
        }

        /** @var PaymentServiceInterface $callerClass */

        try {
            $this->em->beginTransaction();

            $donation->setPaymentStatus('completed')
                ->setPaymentProvider($callerClass::getProvider())
                ->setPaymentReference($paymentReference)
                ->setPaymentCompletedAt(new DateTimeImmutable());

            $donation->getDonor()->setWaitingSince(null);

            // Now place user in matrix
            $this->matrixService->placeDonationInMatrix($donation);

            $this->em->flush();
            $this->em->commit();

        } catch (Exception $e) {
            $this->em->rollback();
            $this->logger->error('Failed to process registration payment: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    protected function processMembershipPayment(Membership $membership, string $paymentReference): void
    {
        try {
            $this->em->beginTransaction();

            $this->membershipService->activateMembership($membership, $paymentReference);

            $this->em->flush();
            $this->em->commit();

        } catch (Exception $e) {
            $this->em->rollback();
            $this->logger->error('Failed to process membership payment: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    protected function processPDonationPayment(PonctualDonation $pdonation, bool $status, string $paymentReference): void
    {
        try {
            $this->em->beginTransaction();

            $this->donationService->ChangePonctualDonationStatus($pdonation, $status, $paymentReference);

            $this->em->flush();
            $this->em->commit();

        } catch (Exception $e) {
            $this->em->rollback();
            $this->logger->error('Failed to process membership payment: ' . $e->getMessage());
            throw $e;
        }
    }

    abstract public static function getProvider(): string;
}
