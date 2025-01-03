<?php

namespace App\Service;

use App\DTO\RegistrationDTO;
use App\Entity\User;
use App\Event\UserRegistrationEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ReferralService $referralService,
        private readonly EmailService $emailService
    ) {}

    public function registerUser(RegistrationDTO $dto, ?User $referrer = null): User
    {
        $user = new User();
        $user->setEmail($dto->email)
            ->setFirstName($dto->firstName)
            ->setLastName($dto->lastName)
            ->setProjectDescription($dto->projectDescription)
            ->setRegistrationPaymentStatus('pending')
            ->setWaitingSince(new \DateTime())
            ->setReferrer($referrer)
            ->setRoles(['ROLE_USER']);
        // Note: Remove setIsVerified as it's controlled by payment status

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        // Setup referral relationship and initial flower
        $this->referralService->setupNewUser($user, $referrer);

        // Persist user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Dispatch registration event
        $event = new UserRegistrationEvent($user);
        $this->eventDispatcher->dispatch($event, UserRegistrationEvent::NAME);

        return $user;
    }
}
