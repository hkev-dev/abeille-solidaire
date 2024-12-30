<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class ReferralService
{
    public function __construct(
        private UserRepository $userRepository,
        private FlowerRepository $flowerRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function validateReferralCode(string $referralCode): ?User
    {
        return $this->userRepository->findByReferralCode($referralCode);
    }

    public function setupNewUser(User $user, User $referrer): void
    {
        // Set referrer
        $user->setReferrer($referrer);

        // Assign first flower (Level 1 - Violette)
        $initialFlower = $this->flowerRepository->findOneBy(['level' => 1]);
        if (!$initialFlower) {
            throw new \RuntimeException('Initial flower (level 1) not found in the database.');
        }
        $user->setCurrentFlower($initialFlower);

        // Generate unique referral code
        $user->setReferralCode($this->generateUniqueReferralCode());

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $code = bin2hex(random_bytes(16));
        } while ($this->userRepository->findByReferralCode($code) !== null);

        return $code;
    }
}
