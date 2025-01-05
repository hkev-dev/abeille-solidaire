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
        private readonly FlowerRepository $flowerRepository
    ) {}

    public function createRegistrationDonation(
        User $donor,
        string $paymentMethod,
        string $transactionId,
        ?array $cryptoDetails = null
    ): Donation {
        $violetteFlower = $this->flowerRepository->findOneBy(['name' => 'Violette']);
        $recipient = $this->matrixPlacementService->findNextAvailablePosition($violetteFlower);

        $donation = new Donation();
        $donation->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount(25.00)
            ->setDonationType('registration')
            ->setFlower($violetteFlower)
            ->setCyclePosition(1)
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
