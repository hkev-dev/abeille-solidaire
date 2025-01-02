<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class WaitingRoomService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository
    ) {
    }

    public function cleanupExpiredRegistrations(int $daysThreshold = 90): void
    {
        $threshold = new \DateTime("-{$daysThreshold} days");

        $expiredUsers = $this->userRepository->findExpiredWaitingRoomUsers($threshold);

        foreach ($expiredUsers as $user) {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
    }

    public function addToWaitingRoom(User $user): void
    {
        $user->setWaitingSince(new \DateTime());
        $user->setRegistrationPaymentStatus('pending');
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
