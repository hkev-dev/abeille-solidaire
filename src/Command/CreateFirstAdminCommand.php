<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Flower;
use App\Entity\Donation;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use App\Service\DonationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-first-admin',
    description: 'Creates the first admin user and initializes the system'
)]
class CreateFirstAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly FlowerRepository $flowerRepository,
        private readonly UserRepository $userRepository,
        private readonly DonationService $donationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Verify system state
        if ($this->userRepository->count([]) > 0) {
            $io->error('Users already exist in the system. This command can only be used for initial setup.');
            return Command::FAILURE;
        }

        $io->title('Creating First Admin User');

        // 2. Collect admin information
        $email = $io->ask('Admin email', null, function ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Invalid email address');
            }
            return $email;
        });

        $username = $io->ask('Admin username', null, function ($username) {
            if (strlen($username) < 3) {
                throw new \RuntimeException('Username must be at least 3 characters long');
            }
            return $username;
        });

        $password = $io->askHidden('Admin password', function ($password) {
            if (strlen($password) < 8) {
                throw new \RuntimeException('Password must be at least 8 characters long');
            }
            return $password;
        });

        $firstName = $io->ask('First name');
        $lastName = $io->ask('Last name');
        
        // Add phone number input
        $phone = $io->ask('Phone number (e.g., +33612345678)', null, function ($phone) {
            if (!preg_match('/^\+?[1-9]\d{1,14}$/', $phone)) {
                throw new \RuntimeException('Invalid phone number format. Please use international format (e.g., +33612345678)');
            }
            return $phone;
        });

        try {
            $this->entityManager->beginTransaction();

            // 3. Create and initialize Violette flower
            $violette = $this->initializeVioletteFlower();

            // 4. Create admin user
            $admin = $this->createAdminUser(
                $email,
                $username,
                $password,
                $firstName,
                $lastName,
                $violette,
                $phone // Add phone number
            );

            // 5. Create initial system donation
            $this->createInitialDonation($admin, $violette);

            $this->entityManager->flush();
            $this->entityManager->commit();

            $io->success([
                'First admin user created successfully!',
                sprintf('Email: %s', $email),
                sprintf('Username: %s', $username),
                'System has been initialized'
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $io->error('Failed to create admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function initializeVioletteFlower(): Flower
    {
        $violette = $this->flowerRepository->findOneBy(['name' => 'Violette']);
        if (!$violette) {
            $violette = new Flower();
            $violette->setName('Violette')
                ->setDonationAmount(25.00)
                ->setLevel(1);
            $this->entityManager->persist($violette);
        }
        return $violette;
    }

    private function createAdminUser(
        string $email,
        string $username,
        string $password,
        string $firstName,
        string $lastName,
        Flower $violette,
        string $phone // Add phone number
    ): User {
        $admin = new User();
        $admin->setEmail($email)
            ->setUsername($username)
            ->setPassword($this->passwordHasher->hashPassword($admin, $password))
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPhone($phone) // Add phone number
            ->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER'])
            ->setProjectDescription('Platform Administrator')
            ->setRegistrationPaymentStatus('completed')
            ->setIsVerified(true)
            ->setHasPaidAnnualFee(true)
            ->setIsKycVerified(true)
            ->setKycVerifiedAt(new \DateTimeImmutable())
            ->setReferralCode('ABEILLESOLIDAIRE')
            ->setCurrentFlower($violette)
            ->setWalletBalance(0.0)
            ->setCountry('FR'); // Set default country code

        $this->entityManager->persist($admin);
        return $admin;
    }

    private function createInitialDonation(User $admin, Flower $violette): void
    {
        $initialDonation = new Donation();
        $initialDonation->setDonor($admin)
            ->setRecipient($admin)
            ->setAmount(25.00)
            ->setDonationType('registration')
            ->setFlower($violette)
            ->setCyclePosition(1)
            ->setTransactionDate(new \DateTimeImmutable());

        $this->entityManager->persist($initialDonation);
    }
}
