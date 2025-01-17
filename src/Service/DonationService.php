<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\User;
use App\Entity\Flower;
use App\Repository\FlowerRepository;
use Doctrine\ORM\EntityManagerInterface;

class DonationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MatrixPlacementService $matrixPlacementService,
        private readonly FlowerRepository $flowerRepository,
        private readonly FlowerProgressionService $flowerProgressionService
    ) {
    }

    public function createRegistrationDonation(
        User $donor,
        string $paymentMethod,
        string $transactionId,
        ?array $cryptoDetails = null
    ): Donation {
        $violetteFlower = $this->flowerRepository->findOneBy(['name' => 'Violette']);
        $recipient = $this->matrixPlacementService->findNextAvailablePosition($violetteFlower);

        if (!$recipient) {
            // Fallback to finding any eligible recipient in the Violette flower
            $recipient = $this->flowerRepository->findNextRecipientInFlower($violetteFlower);
            
            if (!$recipient) {
                // If still no recipient, use the donor as recipient for first position
                $recipient = $donor;
            }
        }

        $donation = new Donation();
        $donation->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount(25.00)
            ->setDonationType('registration')
            ->setFlower($violetteFlower)
            ->setCyclePosition($this->determineInitialPosition($violetteFlower))
            ->setTransactionDate(new \DateTimeImmutable());

        switch ($paymentMethod) {
            case 'stripe':
                $donation->setStripePaymentIntentId($transactionId);
                break;

            case 'coinpayments':
                $donation->setCoinpaymentsTransactionId($transactionId);

                // Store crypto-specific details if available
                if ($cryptoDetails) {
                    $donation->setCryptoAmount($cryptoDetails['crypto_amount'])
                        ->setCryptoCurrency($cryptoDetails['crypto_currency'])
                        ->setExchangeRate($cryptoDetails['exchange_rate'])
                        ->setConfirmationsNeeded($cryptoDetails['confirms_needed'] ?? null)
                        ->setStatusUrl($cryptoDetails['status_url'] ?? null);
                }
                break;
        }

        $this->entityManager->persist($donation);
        $this->entityManager->flush();

        return $donation;
    }

    public function createDonation(
        User $donor,
        User $recipient,
        float $amount,
        string $type,
        string $paymentMethod,
        string $transactionId,
        ?array $cryptoDetails = null
    ): Donation {
        $donation = new Donation();
        $donation->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount($amount)
            ->setDonationType($type)
            ->setTransactionDate(new \DateTimeImmutable());

        if ($type === 'direct') {
            $donation->setFlower($donor->getCurrentFlower())
                ->setCyclePosition($this->calculateCyclePosition($recipient));
        }

        // Set payment-specific details
        switch ($paymentMethod) {
            case 'stripe':
                $donation->setStripePaymentIntentId($transactionId);
                break;

            case 'coinpayments':
                $donation->setCoinpaymentsTransactionId($transactionId);

                // Store crypto-specific details if available
                if ($cryptoDetails) {
                    $donation->setCryptoAmount($cryptoDetails['crypto_amount'])
                        ->setCryptoCurrency($cryptoDetails['crypto_currency'])
                        ->setExchangeRate($cryptoDetails['exchange_rate'])
                        ->setConfirmationsNeeded($cryptoDetails['confirms_needed'] ?? null)
                        ->setStatusUrl($cryptoDetails['status_url'] ?? null);
                }
                break;
        }

        $this->entityManager->persist($donation);
        $this->entityManager->flush();

        // Check for flower progression after donation
        if ($type === 'direct') {
            $this->flowerProgressionService->checkAndProcessProgression($recipient);
        }

        return $donation;
    }

    public function createMembershipDonation(
        User $donor,
        string $paymentMethod,
        string $transactionId,
        ?array $cryptoDetails = null
    ): Donation {
        // Fix: Use native PostgreSQL query with proper JSON array contains operator
        $sql = 'SELECT id FROM "user" WHERE roles::jsonb @> \'["ROLE_ADMIN"]\'::jsonb LIMIT 1';
        $stmt = $this->entityManager->getConnection()->executeQuery($sql);
        $adminId = $stmt->fetchOne();

        if (!$adminId) {
            throw new \RuntimeException('No admin user found to receive membership payment');
        }

        $recipient = $this->entityManager->getRepository(User::class)->find($adminId);
        if (!$recipient) {
            throw new \RuntimeException('Admin user not found');
        }

        $violetteFlower = $this->flowerRepository->findOneBy(['name' => 'Violette']);
        if (!$violetteFlower) {
            throw new \RuntimeException('Violette flower not found');
        }

        $donation = new Donation();
        $donation->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount(25.00)
            ->setDonationType('membership')
            ->setTransactionDate(new \DateTimeImmutable())
            ->setFlower($violetteFlower)  // Add this line
            ->setCyclePosition(0);  // Add this line with position 0 for membership donations

        // Set payment method specific details
        switch ($paymentMethod) {
            case 'stripe':
                $donation->setStripePaymentIntentId($transactionId);
                break;

            case 'coinpayments':
                $donation->setCoinpaymentsTransactionId($transactionId);
                
                if ($cryptoDetails) {
                    $donation->setCryptoAmount($cryptoDetails['crypto_amount'])
                        ->setCryptoCurrency($cryptoDetails['crypto_currency'])
                        ->setExchangeRate($cryptoDetails['exchange_rate'])
                        ->setConfirmationsNeeded($cryptoDetails['confirms_needed'] ?? null)
                        ->setStatusUrl($cryptoDetails['status_url'] ?? null);
                }
                break;

            default:
                throw new \InvalidArgumentException('Invalid payment method');
        }

        $this->entityManager->persist($donation);
        $this->entityManager->flush();

        return $donation;
    }

    private function calculateCyclePosition(User $recipient): int
    {
        // Get count of existing donations for this recipient in current flower
        $existingDonations = $this->entityManager->getRepository(Donation::class)
            ->count([
                'recipient' => $recipient,
                'flower' => $recipient->getCurrentFlower(),
                'donation_type' => 'direct'
            ]);

        return $existingDonations + 1;
    }

    private function determineInitialPosition(Flower $flower): int
    {
        $existingPositions = $this->entityManager->getRepository(Donation::class)
            ->createQueryBuilder('d')
            ->select('d.cyclePosition')
            ->where('d.flower = :flower')
            ->andWhere('d.donationType IN (:types)')
            ->setParameter('flower', $flower)
            ->setParameter('types', ['registration', 'direct'])
            ->getQuery()
            ->getArrayResult();

        $usedPositions = array_column($existingPositions, 'cyclePosition');
        
        // Find first available position from 1 to 16
        for ($i = 1; $i <= 16; $i++) {
            if (!in_array($i, $usedPositions)) {
                return $i;
            }
        }

        // If all positions are taken (shouldn't happen due to matrix checks)
        return 1;
    }

    public function processDonation(User $donor, Flower $flower, string $donationType): ?Donation
    {
        if ($this->matrixPlacementService->isMatrixFull($flower)) {
            // Handle matrix overflow - could create new matrix or waitlist
            return null;
        }

        $recipient = $this->matrixPlacementService->findNextAvailablePosition($flower);
        if (!$recipient) {
            return null;
        }

        try {
            $position = array_search(
                null,
                $this->matrixPlacementService->getMatrixState($flower)
            );

            $this->matrixPlacementService->lockPosition($position, $flower);

            $donation = new Donation();
            $donation->setDonor($donor)
                ->setRecipient($recipient)
                ->setFlower($flower)
                ->setDonationType($donationType)
                ->setCyclePosition($position);

            $this->entityManager->persist($donation);
            $this->entityManager->flush();

            return $donation;
        } catch (\Exception $e) {
            // Handle error and possibly retry
            return null;
        }
    }
}
