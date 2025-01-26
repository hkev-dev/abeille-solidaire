<?php

namespace App\Service;

use App\DTO\RegistrationDTO;
use App\Entity\User;
use App\Event\UserRegistrationEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class UserRegistrationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
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
                ->setWalletBalance(0.0);

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
