<?php

namespace App\Service;

use App\Entity\Donation;
use App\Entity\User;
use App\Entity\Flower;
use App\Repository\FlowerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DonationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MatrixPlacementService $matrixPlacementService,
        private readonly FlowerRepository $flowerRepository,
        private readonly EventDispatcherInterface $eventDispatcher // Add this
    ) {
    }

    public function createRegistrationDonation(
        User $donor,
        string $paymentMethod,
        string $transactionId,
        ?array $cryptoDetails = null
    ): Donation {
        $violetteFlower = $this->flowerRepository->findOneBy(['name' => 'Violette']);
        if (!$violetteFlower) {
            throw new \RuntimeException('Violette flower not found');
        }

        // Find next available position in matrix
        $matrixData = $this->matrixPlacementService->findNextAvailablePosition($violetteFlower);
        if (!$matrixData) {
            throw new \RuntimeException('No available positions in matrix');
        }

        // Create registration donation with proper recipient
        $donation = new Donation();
        $donation->setDonor($donor)
            ->setAmount(25.00)
            ->setDonationType('registration')
            ->setFlower($violetteFlower)
            ->setCyclePosition($matrixData['position'])
            ->setTransactionDate(new \DateTimeImmutable());

        // Handle recipient based on matrix position
        if ($matrixData['position'] === 1) {
            // Root user donates to themselves
            $donation->setRecipient($donor);
        } else {
            // Non-root users donate to their parent
            if (!$matrixData['parent']) {
                throw new \RuntimeException('Parent user not found for non-root position');
            }
            $donation->setRecipient($matrixData['parent']);
        }

        // Update donor's matrix information
        $donor->setMatrixDepth($matrixData['depth'])
            ->setMatrixPosition($matrixData['position'])
            ->setParent($matrixData['parent']);

        // Lock the matrix position
        $this->matrixPlacementService->lockPosition($matrixData['position'], $violetteFlower);

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

        if ($type === 'direct' || $type === 'matrix_propagation') {
            $flower = $donor->getCurrentFlower();
            if (!$flower) {
                throw new \RuntimeException('Donor does not have a current flower');
            }

            // For matrix-based donations, calculate next position
            $position = $this->matrixPlacementService->findNextPositionForUser($recipient, $flower);
            if (!$position) {
                throw new \RuntimeException('No available matrix position for donation');
            }

            $donation->setFlower($flower)
                ->setCyclePosition($position);

            // Lock the position in matrix
            $this->matrixPlacementService->lockPosition($position, $flower);
        }

        // Set payment-specific details
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
        }

        $this->entityManager->persist($donation);
        $this->entityManager->flush();

        // Check for flower progression after matrix-based donations
        if ($type === 'direct' || $type === 'matrix_propagation') {
            // Instead of directly calling FlowerProgressionService, dispatch an event
            $event = new DonationProcessedEvent($recipient, $donor, $type);
            $this->eventDispatcher->dispatch($event, DonationProcessedEvent::NAME);
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

    public function processDonation(User $donor, Flower $flower, string $donationType): ?Donation
    {
        // Check if matrix can accept new donations
        if ($this->matrixPlacementService->isMatrixFull($flower)) {
            throw new \RuntimeException('Matrix is full for this flower');
        }

        // Find next position and recipient
        $position = $this->matrixPlacementService->findNextAvailablePosition($flower);
        if (!$position) {
            throw new \RuntimeException('No available position found in matrix');
        }

        // Calculate matrix position details
        $matrixDetails = $this->matrixPlacementService->calculateMatrixPosition($position);

        // Fix: Use findParentUser instead of findPositionRecipient
        $recipient = $this->matrixPlacementService->findParentUser($matrixDetails['depth'], $matrixDetails['position']);

        if (!$recipient) {
            throw new \RuntimeException('Could not find valid recipient in matrix');
        }

        try {
            // Start transaction
            $this->entityManager->beginTransaction();

            // Lock position
            $this->matrixPlacementService->lockPosition($position, $flower);

            // Create donation
            $donation = new Donation();
            $donation->setDonor($donor)
                ->setRecipient($recipient)
                ->setFlower($flower)
                ->setDonationType($donationType)
                ->setCyclePosition($position)
                ->setTransactionDate(new \DateTimeImmutable());

            $this->entityManager->persist($donation);

            // Update recipient's matrix info
            $recipient->setMatrixDepth($matrixDetails['depth'])
                ->setMatrixPosition($matrixDetails['position']);

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $donation;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    /**
     * Helper method to validate matrix position for a donation
     */
    private function validateMatrixPosition(int $position, Flower $flower): bool
    {
        if ($position < 1 || $position > 16) {
            return false;
        }

        $matrixState = $this->matrixPlacementService->getMatrixState($flower);
        return !isset($matrixState[$position]);
    }
}
