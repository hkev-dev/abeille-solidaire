<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Flower;
use App\Repository\UserRepository;
use App\Entity\SystemConfiguration;
use App\Repository\FlowerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:initialize-system',
    description: 'Initializes the Abeille Solidaire system: matrix structure, flower system, and root admin'
)]
class InitializeSystemCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly FlowerRepository $flowerRepository,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Show warning and confirmation
        $io->caution([
            'This command will initialize the entire system and create the root admin user.',
            'It should only be run once on a fresh installation.',
            'Make sure you have:',
            '- A clean database with no existing data',
            '- Proper environment configuration (.env)',
            '- Necessary PHP extensions installed',
            '- Proper permissions on storage directories'
        ]);

        if (!$io->confirm('Are you sure you want to continue?', false)) {
            return Command::SUCCESS;
        }

        // 1. Check system requirements
        $io->section('Checking System Requirements');

        if (!$this->checkSystemRequirements($io)) {
            return Command::FAILURE;
        }

        // 2. Verify system state
        if ($this->userRepository->count([]) > 0) {
            $io->error('Users already exist in the system. This command can only be used for initial setup.');
            return Command::FAILURE;
        }

        // Show initialization steps
        $io->section('Initialization Steps');
        $io->listing([
            'System configuration setup',
            'Flower system initialization (10 levels)',
            'Matrix structure preparation',
            'Root admin user creation',
            'Initial donation and membership setup'
        ]);

        $this->entityManager->beginTransaction();

        try {
            // Create progress bar
            $progressBar = $io->createProgressBar(5);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% -- %message%');

            // Initialize system configuration
            $progressBar->setMessage('Setting up system configuration...');
            $this->initializeSystemConfiguration();
            $progressBar->advance();

            // Initialize all flowers
            $progressBar->setMessage('Initializing flower system...');
            $flowers = $this->initializeFlowers();
            $violette = $flowers['Violette'];
            $progressBar->advance();

            // Initialize matrix structure
            $progressBar->setMessage('Preparing matrix structure...');
            $io->section('Matrix Initialization');
            $io->text([
                'Initializing 4x4 matrix system:',
                '- Creating root node (matrix depth: 0)',
                '- Setting up first position in Violette flower',
                '- Initializing matrix tracking system'
            ]);
            $progressBar->advance();

            // Collect admin information
            $progressBar->setMessage('Creating admin user...');
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

            $progressBar->setMessage('Creating admin user...');
            $this->createAdminUser(
                $email,
                $username,
                $password,
                $firstName,
                $lastName,
                $violette,
                $phone // Add phone number
            );

            $this->entityManager->flush();
            $this->entityManager->commit();

            $io->success([
                'First admin user created successfully!',
                sprintf('Email: %s', $email),
                sprintf('Username: %s', $username),
                'System has been initialized',
                '',
                'Matrix System Status:',
                '- Root node created (matrix depth: 0, position: 1)',
                '- All flower levels initialized (Violette -> Rose Gold)',
                '- First matrix position occupied',
                '- Matrix configuration parameters set',
                '',
                'Flower System Status:',
                '- Level 1: Violette (25€)',
                '- Level 2: Coquelicot (50€)',
                '- Level 3: Bouton d\'Or (100€)',
                '- Level 4: Laurier Rose (200€)',
                '- Level 5: Tulipe (400€)',
                '- Level 6: Germini (800€)',
                '- Level 7: Lys (1,600€)',
                '- Level 8: Clématite (3,200€)',
                '- Level 9: Chrysanthème (6,400€)',
                '- Level 10: Rose Gold (12,800€)',
                '',
                'Next Steps:',
                '- New users will be placed in matrix chronologically',
                '- Each node can have up to 4 direct children',
                '- Matrix levels will be filled left to right',
                '- System ready for user registration'
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $io->error('Failed to create admin user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function initializeFlowers(): array
    {
        $flowerConfigs = [
            'Violette' => ['level' => 1, 'amount' => 25.00],
            'Coquelicot' => ['level' => 2, 'amount' => 50.00],
            'Bouton d\'Or' => ['level' => 3, 'amount' => 100.00],
            'Laurier Rose' => ['level' => 4, 'amount' => 200.00],
            'Tulipe' => ['level' => 5, 'amount' => 400.00],
            'Germini' => ['level' => 6, 'amount' => 800.00],
            'Lys' => ['level' => 7, 'amount' => 1600.00],
            'Clématite' => ['level' => 8, 'amount' => 3200.00],
            'Chrysanthème' => ['level' => 9, 'amount' => 6400.00],
            'Rose Gold' => ['level' => 10, 'amount' => 12800.00],
        ];

        $flowers = [];
        $output = new SymfonyStyle(new ArrayInput([]), new ConsoleOutput());

        foreach ($flowerConfigs as $name => $config) {
            $flower = $this->flowerRepository->findOneBy(['name' => $name]);
            if (!$flower) {
                $flower = new Flower();
                $flower->setName($name)
                    ->setDonationAmount($config['amount'])
                    ->setLevel($config['level']);
                $this->entityManager->persist($flower);
                $output->text(sprintf(
                    'Created flower: %s (Level %d, Amount: %.2f€)',
                    $name,
                    $config['level'],
                    $config['amount']
                ));
            } else {
                $output->text(sprintf('Flower already exists: %s', $name));
            }
            $flowers[$name] = $flower;
        }

        return $flowers;
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
            ->setPhone($phone)
            ->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER'])
            ->setRegistrationPaymentStatus('completed')
            ->setHasPaidAnnualFee(true)
            ->setIsKycVerified(true)
            ->setKycVerifiedAt(new \DateTimeImmutable())
            ->setCurrentFlower($violette)
            ->setWalletBalance(0.0)
            ->setCountry('FR')
            // Matrix-specific initialization
            ->setMatrixDepth(0) // Root node has depth 0
            ->setMatrixPosition(1); // First position in the matrix

        $this->entityManager->persist($admin);
        return $admin;
    }

    private function initializeSystemConfiguration(): void
    {
        $configs = [
            SystemConfiguration::KEY_WITHDRAWAL_FEE => ['0.06', 'float', 'Withdrawal fee (6%)'],
            SystemConfiguration::KEY_MIN_WITHDRAWAL => ['50.00', 'float', 'Minimum withdrawal amount'],
            SystemConfiguration::KEY_MAX_WITHDRAWAL => ['10000.00', 'float', 'Maximum withdrawal amount per week'],
            SystemConfiguration::KEY_MEMBERSHIP_FEE => ['25.00', 'float', 'Annual membership fee'],
            SystemConfiguration::KEY_REGISTRATION_FEE => ['25.00', 'float', 'One-time registration fee'],
            SystemConfiguration::KEY_WAITING_ROOM_EXPIRY_DAYS => ['30', 'integer', 'Days until waiting room entry expires'],
            SystemConfiguration::KEY_MATRIX_GRACE_PERIOD_DAYS => ['30', 'integer', 'Grace period days for matrix progression'],
            SystemConfiguration::KEY_MAX_MATRIX_DEPTH => ['10', 'integer', 'Maximum depth of matrix structure'],
            SystemConfiguration::KEY_MIN_MATRIX_DEPTH_FOR_WITHDRAWAL => ['3', 'integer', 'Minimum matrix depth required for withdrawals'],
        ];

        foreach ($configs as $key => [$value, $type, $description]) {
            $config = new SystemConfiguration();
            $config->setKey($key)
                ->setValue($value)
                ->setType($type)
                ->setDescription($description);
            $this->entityManager->persist($config);
        }
    }

    private function checkSystemRequirements(SymfonyStyle $io): bool
    {
        $requirements = [
            'PHP Version >= 8.4' => version_compare(PHP_VERSION, '8.4.0', '>='),
            'PDO Extension' => extension_loaded('pdo'),
            'PDO PostgreSQL Extension' => extension_loaded('pdo_pgsql'),
            'Intl Extension' => extension_loaded('intl'),
            'JSON Extension' => extension_loaded('json'),
            'Ctype Extension' => extension_loaded('ctype'),
            'APCu Extension' => extension_loaded('apcu'),
            'var/ Directory Writable' => is_writable($this->getProjectDir() . '/var'),
            'public/ Directory Writable' => is_writable($this->getProjectDir() . '/public'),
            '.env File Exists' => file_exists($this->getProjectDir() . '/.env'),
        ];

        $allPassed = true;
        foreach ($requirements as $requirement => $passed) {
            if ($passed) {
                $io->writeln(" <info>✓</info> {$requirement}");
            } else {
                $io->writeln(" <error>✗</error> {$requirement}");
                $allPassed = false;
            }
        }

        if (!$allPassed) {
            $io->error('Some system requirements are not met. Please fix them before proceeding.');
            return false;
        }

        $io->success('All system requirements are met.');
        return true;
    }

    private function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }
}
