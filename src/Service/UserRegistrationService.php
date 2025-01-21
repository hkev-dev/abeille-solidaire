<?php

namespace App\Service;

use App\DTO\RegistrationDTO;
use App\Entity\User;
use App\Entity\Flower;
use App\Event\UserRegistrationEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;
use App\Repository\FlowerRepository;

class UserRegistrationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger,
        private readonly FlowerRepository $flowerRepository, // Add this
        private readonly MatrixPlacementService $matrixPlacementService // Add this
    ) {
    }

    public function registerUser(RegistrationDTO $dto): User
    {
        try {
            $user = new User();
            $user->setEmail($dto->email)
                ->setFirstName($dto->firstName)
                ->setLastName($dto->lastName)
                ->setRegistrationPaymentStatus('pending')
                ->setWaitingSince(new \DateTime())
                ->setRoles(['ROLE_USER'])
                ->setUsername($dto->username)
                ->setAccountType($dto->accountType)
                ->setCountry($dto->country)
                ->setPhone($dto->phone)
                ->setOrganizationName($dto->organizationName)
                ->setOrganizationNumber($dto->organizationNumber)
                ->setWalletBalance(0.0)
                ->setIsVerified(false)
                ->setHasPaidAnnualFee(false)
                // Initialize matrix properties with default values
                ->setMatrixDepth(0)
                ->setMatrixPosition(0)
                ->setParent(null);

            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
            $user->setPassword($hashedPassword);

            // Persist user
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->logger->info('New user registered in waiting room', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail()
            ]);

            // Dispatch registration event
            $event = new UserRegistrationEvent($user);
            $this->eventDispatcher->dispatch($event, UserRegistrationEvent::NAME);

            // Send welcome email
            $this->emailService->sendWelcomeEmail($user);

            return $user;

        } catch (\Exception $e) {
            $this->logger->error('Failed to register new user', [
                'error' => $e->getMessage(),
                'email' => $dto->email
            ]);
            throw $e;
        }
    }
}
