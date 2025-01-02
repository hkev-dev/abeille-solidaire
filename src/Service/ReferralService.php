<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Flower;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;

class ReferralService
{
    private const REFERRAL_CODE_LENGTH = 32;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly FlowerRepository $flowerRepository,
        private readonly MatrixPlacementService $matrixPlacementService
    ) {}

    /**
     * @throws RandomException
     */
    public function setupNewUser(User $user, User $referrer): void
    {
        // Set referral relationship
        $user->setReferrer($referrer);
        
        // Generate unique referral code
        $user->setReferralCode($this->generateUniqueReferralCode());
        
        // Set initial flower (Violette)
        $violetteFlower = $this->flowerRepository->findOneBy(['name' => 'Violette']);
        if (!$violetteFlower) {
            throw new \RuntimeException('Violette flower not found in database');
        }
        $user->setCurrentFlower($violetteFlower);
        
        // Initialize wallet balance
        $user->setWalletBalance(0.0);
        
        // Set waiting room timestamp
        $user->setWaitingSince(new \DateTime());
        
        // Set initial registration payment status
        $user->setRegistrationPaymentStatus('pending');

        $this->entityManager->persist($user);
    }

    public function validateReferralCode(string $code): ?User
    {
        $referrer = $this->userRepository->findOneBy(['referralCode' => $code]);
        
        if (!$referrer) {
            return null;
        }

        // Check if referrer is verified and has completed registration payment
        if (!$referrer->isVerified() || $referrer->getRegistrationPaymentStatus() !== 'completed') {
            return null;
        }

        return $referrer;
    }

    public function getReferralPath(User $user): array
    {
        $path = [];
        $current = $user;

        while ($current->getReferrer() !== null) {
            $path[] = $current->getReferrer();
            $current = $current->getReferrer();

            // Prevent infinite loops in case of circular references
            if (count($path) > 10) {
                break;
            }
        }

        return $path;
    }

    /**
     * @throws RandomException
     */
    private function generateUniqueReferralCode(): string
    {
        do {
            $code = bin2hex(random_bytes(self::REFERRAL_CODE_LENGTH / 2));
        } while ($this->userRepository->findOneBy(['referralCode' => $code]));

        return $code;
    }

    public function getDirectReferrals(User $user): array
    {
        return $this->userRepository->findBy(
            ['referrer' => $user],
            ['createdAt' => 'ASC'],
            4
        );
    }

    public function canAcceptNewReferrals(User $user): bool
    {
        $directReferrals = $this->getDirectReferrals($user);
        return count($directReferrals) < 4;
    }

    public function followReferrerInFlower(User $user, Flower $flower): void
    {
        $referrer = $user->getReferrer();
        if (!$referrer) {
            return;
        }

        // Check if referrer has reached this flower
        if ($referrer->getCurrentFlower()?->getId() >= $flower->getId()) {
            // Find next available position in referrer's matrix
            $position = $this->matrixPlacementService->findNextPositionInReferrerMatrix($referrer, $flower);
            if ($position) {
                $user->setCurrentFlower($flower);
                // Additional logic for matrix placement will be handled by MatrixPlacementService
            }
        } else {
            // Place in waiting state for this flower
            // This could be handled by a separate service or additional status field
            $user->setCurrentFlower($flower);
        }

        $this->entityManager->flush();
    }
}
