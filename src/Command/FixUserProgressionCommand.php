<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Flower;
use App\Entity\Donation;
use App\Repository\UserRepository;
use App\Repository\FlowerRepository;
use App\Repository\DonationRepository;
use App\Service\MatrixPlacementService;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\FlowerProgressionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fix-user-progression',
    description: 'Fixes user progression and matrix positions for existing users'
)]
class FixUserProgressionCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly FlowerRepository $flowerRepository,
        private readonly DonationRepository $donationRepository,
        private readonly FlowerProgressionService $progressionService,
        private readonly MatrixPlacementService $matrixPlacementService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:fix-user-progression')
            ->setDescription('Fixes user progression and matrix positions for existing users')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run in simulation mode without making changes')
            ->addOption('verify-only', null, InputOption::VALUE_NONE, 'Only verify system state without fixes')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force fixes even if verification passes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $verifyOnly = $input->getOption('verify-only');
        $force = $input->getOption('force');

        try {
            // 1. System Verification
            $verificationResults = $this->verifySystemState($io);
            if ($verifyOnly) {
                return $this->handleVerificationResults($io, $verificationResults);
            }

            if (!$force && empty($verificationResults['errors'])) {
                $io->success('System state is correct. No fixes needed.');
                return Command::SUCCESS;
            }

            // 2. Begin Fixes
            if (!$isDryRun) {
                $this->entityManager->beginTransaction();
            }

            try {
                $this->fixDatabaseIntegrity($io, $isDryRun);
                $this->fixUserPositions($io, $isDryRun);
                $this->fixDonationProgress($io, $isDryRun);
                $this->fixSolidaritySystem($io, $isDryRun);
                $this->verifyAndFixFlowerProgression($io, $isDryRun);

                if (!$isDryRun) {
                    $this->entityManager->commit();
                    $io->success('System has been fixed and verified');
                } else {
                    $io->success('Dry run completed. Run without --dry-run to apply fixes');
                }

                return Command::SUCCESS;
            } catch (\Exception $e) {
                if (!$isDryRun) {
                    $this->entityManager->rollback();
                }
                throw $e;
            }
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function verifySystemState(SymfonyStyle $io): array
    {
        $io->section('Verifying System State');
        $errors = [];
        $warnings = [];

        // Check database integrity
        $integrityIssues = $this->checkDatabaseIntegrity();
        if (!empty($integrityIssues)) {
            $errors['database'] = $integrityIssues;
        }

        // Check user positions
        $positionIssues = $this->checkUserPositions();
        if (!empty($positionIssues)) {
            $errors['positions'] = $positionIssues;
        }

        // Check donation progress
        $progressIssues = $this->checkDonationProgress();
        if (!empty($progressIssues)) {
            $errors['progress'] = $progressIssues;
        }

        // Check solidarity system
        $solidarityIssues = $this->checkSolidaritySystem();
        if (!empty($solidarityIssues)) {
            $errors['solidarity'] = $solidarityIssues;
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    private function checkDatabaseIntegrity(): array
    {
        $issues = [];

        // Check for orphaned donations
        $orphanedDonations = $this->entityManager->createQuery(
            'SELECT COUNT(d) FROM App\Entity\Donation d
             WHERE d.donor IS NULL OR d.recipient IS NULL'
        )->getSingleScalarResult();

        if ($orphanedDonations > 0) {
            $issues[] = "Found {$orphanedDonations} orphaned donations";
        }

        // Check for invalid flower assignments
        $invalidFlowers = $this->entityManager->createQuery(
            'SELECT COUNT(u) FROM App\Entity\User u
             WHERE u.currentFlower IS NULL
             AND u.registrationPaymentStatus = :status'
        )->setParameter('status', 'completed')
            ->getSingleScalarResult();

        if ($invalidFlowers > 0) {
            $issues[] = "Found {$invalidFlowers} users with invalid flower assignments";
        }

        return $issues;
    }

    private function fixDatabaseIntegrity(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Fixing Database Integrity');

        // Fix orphaned donations
        $this->fixOrphanedDonations($io, $isDryRun);

        // Fix invalid flower assignments
        $this->fixInvalidFlowerAssignments($io, $isDryRun);

        // Fix inconsistent user states
        $this->fixInconsistentUserStates($io, $isDryRun);

        $io->text('Database integrity fixes completed');
    }

    private function fixUserPositions(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Fixing User Positions');

        // Reset cycle positions
        $this->resetCyclePositions($io, $isDryRun);

        // Rebuild matrix positions
        $this->rebuildMatrixPositions($io, $isDryRun);

        $io->text('User position fixes completed');
    }

    private function fixSolidaritySystem(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Fixing Solidarity System');

        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            $io->text("Processing solidarity for user: {$user->getEmail()}");

            $donations = $this->donationRepository->findBy([
                'recipient' => $user,
                'donationType' => ['direct', 'registration']
            ]);

            $totalReceived = array_reduce(
                $donations,
                fn($sum, $donation) => $sum + $donation->getAmount(),
                0
            );

            if ($totalReceived > 0) {
                $expectedSolidarity = $totalReceived * 0.5;
                $actualSolidarity = $this->donationRepository
                    ->findTotalSolidarityDistributed($user);

                if (abs($actualSolidarity - $expectedSolidarity) > 0.01) {
                    if ($isDryRun) {
                        $io->text(sprintf(
                            '  Would fix solidarity mismatch: Expected %.2f, Actual %.2f',
                            $expectedSolidarity,
                            $actualSolidarity
                        ));
                    } else {
                        $this->fixSolidarityMismatch(
                            $user,
                            $expectedSolidarity,
                            $actualSolidarity
                        );
                    }
                }
            }
        }

        $io->text('Solidarity system fixes completed');
    }

    private function resetCyclePositions(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Resetting cycle positions');

        if (!$isDryRun) {
            $this->entityManager->beginTransaction();
        }

        try {
            // Reset all positions first with a single query
            if (!$isDryRun) {
                $qb = $this->entityManager->createQueryBuilder();
                $qb->update(Donation::class, 'd')
                    ->set('d.cyclePosition', 0)  // Set to 0 instead of NULL
                    ->where('d.donationType IN (:types)')
                    ->setParameter('types', ['registration', 'direct'])
                    ->getQuery()
                    ->execute();

                $this->entityManager->flush();
                $this->entityManager->commit();
            }

            // Log the operation
            $io->text('All cycle positions have been reset');

        } catch (\Exception $e) {
            if (!$isDryRun) {
                $this->entityManager->rollback();
            }
            throw $e;
        }
    }

    private function rebuildMatrixPositions(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Rebuilding matrix positions');

        if (!$isDryRun) {
            $this->entityManager->beginTransaction();
        }

        try {
            $flowers = $this->flowerRepository->findBy([], ['level' => 'ASC']);
            foreach ($flowers as $flower) {
                $io->text(sprintf('Processing flower: %s', $flower->getName()));

                $allUsers = $this->userRepository->findByCurrentFlower($flower);
                $matrixNumber = 0;
                $position = 1;
                $usersInCurrentMatrix = [];

                foreach ($allUsers as $user) {
                    // Check if we need to start a new matrix
                    if (count($usersInCurrentMatrix) >= 16) {
                        $matrixNumber++;
                        $position = 1;
                        $usersInCurrentMatrix = [];
                    }

                    if ($isDryRun) {
                        $io->text(sprintf(
                            '  Would assign position %d in matrix %d to user %s',
                            $position,
                            $matrixNumber,
                            $user->getEmail()
                        ));
                    } else {
                        $this->assignMatrixPosition($user, $flower, $position, $matrixNumber);
                    }

                    $usersInCurrentMatrix[] = $user;
                    $position++;
                }

                if (!$isDryRun) {
                    $this->entityManager->flush();
                }
            }

            if (!$isDryRun) {
                $this->entityManager->commit();
            }
        } catch (\Exception $e) {
            if (!$isDryRun) {
                $this->entityManager->rollback();
            }
            throw $e;
        }
    }

    private function assignMatrixPosition(User $user, Flower $flower, int $position, int $matrixNumber): void
    {
        // Find the earliest donation that needs a position (use 0 as default)
        $donation = $this->donationRepository->findOneBy(
            [
                'recipient' => $user,
                'flower' => $flower,
                'donationType' => ['registration', 'direct'],
                'cyclePosition' => 0  // Changed from null
            ],
            ['transactionDate' => 'ASC']
        );

        if ($donation) {
            $donation->setCyclePosition($position);
            $donation->setMatrixNumber($matrixNumber);
            $this->entityManager->persist($donation);
        }
    }

    private function recalculateProgression(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Recalculating user progression');

        $users = $this->userRepository->findAll();
        foreach ($users as $user) {
            $io->text(sprintf('Processing progression for user: %s', $user->getEmail()));

            $currentFlower = $user->getCurrentFlower();
            if (!$currentFlower) {
                continue;
            }

            try {
                if ($isDryRun) {
                    // Just show the info in dry run
                    $cycleInfo = $this->donationRepository->getCurrentCycleInfo($user, $currentFlower);
                    $io->text(sprintf(
                        '  Current cycle info: %d completed cycles, %d donations in current cycle',
                        $cycleInfo['totalCompletedCycles'],
                        $cycleInfo['donationsInCurrentCycle']
                    ));
                } else {
                    // Wrap each user's progression in its own transaction
                    $this->entityManager->beginTransaction();
                    try {
                        $this->progressionService->checkAndProcessProgression($user);
                        $this->entityManager->flush();
                        $this->entityManager->commit();
                    } catch (\Exception $e) {
                        $this->entityManager->rollback();
                        $io->error(sprintf(
                            'Error processing user %s: %s',
                            $user->getEmail(),
                            $e->getMessage()
                        ));
                    }
                }
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Error calculating progression for user %s: %s',
                    $user->getEmail(),
                    $e->getMessage()
                ));
            }
        }
    }

    private function fixSolidarityDonations(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Fixing solidarity donations');

        if (!$isDryRun) {
            $this->entityManager->beginTransaction();
        }

        try {
            $users = $this->userRepository->findAll();
            foreach ($users as $user) {
                $donations = $this->donationRepository->findBy([
                    'recipient' => $user,
                    'donationType' => ['direct', 'registration']
                ]);

                $totalReceived = array_reduce(
                    $donations,
                    fn($sum, $donation) => $sum + $donation->getAmount(),
                    0
                );

                if ($totalReceived > 0) {
                    $expectedSolidarity = $totalReceived * 0.5;
                    $actualSolidarity = $this->donationRepository->findTotalSolidarityDistributed($user);

                    if (abs($actualSolidarity - $expectedSolidarity) > 0.01) {
                        $io->text(sprintf(
                            'User %s has incorrect solidarity donations (Expected: %.2f, Actual: %.2f)',
                            $user->getEmail(),
                            $expectedSolidarity,
                            $actualSolidarity
                        ));

                        if (!$isDryRun) {
                            $diff = $expectedSolidarity - $actualSolidarity;
                            if ($diff > 0) {
                                $recipient = $this->progressionService->findValidSolidarityRecipient();
                                if ($recipient) {
                                    $this->createSolidarityDonation($user, $recipient, $diff);
                                }
                            }
                        }
                    }
                }
            }

            if (!$isDryRun) {
                $this->entityManager->flush();
                $this->entityManager->commit();
            }
        } catch (\Exception $e) {
            if (!$isDryRun) {
                $this->entityManager->rollback();
            }
            throw $e;
        }
    }

    private function createSolidarityDonation(User $donor, User $recipient, float $amount): void
    {
        // Get the recipient's flower
        $flower = $recipient->getCurrentFlower();

        // Find the next available position in the matrix
        $nextPosition = $this->matrixPlacementService->findNextAvailablePosition($flower) ?? 1;
        $matrixNumber = floor(($nextPosition - 1) / 16);
        $cyclePosition = (($nextPosition - 1) % 16) + 1;

        $donation = new Donation();
        $donation
            ->setDonationType('solidarity')
            ->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount($amount)
            ->setTransactionDate(new \DateTimeImmutable())
            ->setFlower($flower)
            ->setCyclePosition($cyclePosition)
            ->setMatrixNumber($matrixNumber)
            ->setSolidarityDistributionStatus(Donation::SOLIDARITY_STATUS_DISTRIBUTED);

        $this->entityManager->persist($donation);
    }

    private function handleAdminProgression(SymfonyStyle $io, bool $isDryRun): void
    {
        // Use native SQL with proper PostgreSQL jsonb array containment
        $conn = $this->entityManager->getConnection();
        $sql = <<<SQL
            SELECT id 
            FROM "user" 
            WHERE roles::jsonb @> '"ROLE_ADMIN"'::jsonb 
            LIMIT 1
        SQL;

        $result = $conn->executeQuery($sql)->fetchOne();
        $admin = $result ? $this->userRepository->find($result) : null;

        if (!$admin) {
            return;
        }

        $io->section('Processing Admin User Progression');

        // Get required flowers
        $violette = $this->flowerRepository->findOneBy(['name' => 'Violette']);
        $coquelicot = $this->flowerRepository->findOneBy(['name' => 'Coquelicot']);

        if (!$violette || !$coquelicot) {
            $io->warning('Required flowers not found, skipping admin progression');
            return;
        }

        if ($isDryRun) {
            $io->text(sprintf('Would process admin user: %s', $admin->getEmail()));
            $io->text(sprintf('Current flower: %s', $admin->getCurrentFlower()->getName()));
            $io->text('Would create 3 system donations and 1 solidarity donation');
            $io->text('Would progress admin to Coquelicot flower');
            return;
        }

        try {
            $this->entityManager->beginTransaction();

            // Create initial matrix position for admin
            $matrixNumber = 0;
            $basePosition = 1;

            // Create system donations (3 more donations to complete cycle)
            for ($i = 2; $i <= 4; $i++) {
                $donation = new Donation();
                $donation
                    ->setDonor($admin)
                    ->setRecipient($admin)
                    ->setAmount(25.00)
                    ->setDonationType('direct')
                    ->setFlower($violette)
                    ->setCyclePosition($i)
                    ->setTransactionDate(new \DateTimeImmutable())
                    ->setMatrixNumber($matrixNumber);

                $this->entityManager->persist($donation);
            }

            // Create solidarity donation with proper position
            $donation = new Donation();
            $donation
                ->setDonor($admin)
                ->setRecipient($admin)
                ->setAmount(50.00)
                ->setDonationType('solidarity')
                ->setFlower($violette)
                ->setCyclePosition($basePosition)  // Place in first position
                ->setMatrixNumber($matrixNumber)
                ->setTransactionDate(new \DateTimeImmutable())
                ->setSolidarityDistributionStatus(Donation::SOLIDARITY_STATUS_DISTRIBUTED);

            $this->entityManager->persist($donation);

            // Progress admin to Coquelicot
            $admin->setCurrentFlower($coquelicot);
            $this->entityManager->persist($admin);

            $this->entityManager->flush();
            $this->entityManager->commit();

            $io->success('Admin progression completed successfully');

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    private function checkUserPositions(): array
    {
        $issues = [];

        // Check for users with invalid matrix positions
        $invalidPositions = $this->entityManager->createQuery(
            'SELECT COUNT(d) FROM App\Entity\Donation d
             WHERE (d.cyclePosition > 16
             OR d.cyclePosition < 1)
             AND d.donationType IN (:types)'
        )->setParameter('types', ['direct', 'registration', 'referral_placement'])
            ->getSingleScalarResult();

        if ($invalidPositions > 0) {
            $issues[] = "Found {$invalidPositions} donations with invalid matrix positions";
        }

        // Check for duplicate positions within same flower - Fixed query syntax
        $duplicates = $this->entityManager->createQuery(
            'SELECT COUNT(DISTINCT d1.id)
             FROM App\Entity\Donation d1, App\Entity\Donation d2
             WHERE d1.id < d2.id
             AND d1.flower = d2.flower
             AND d1.cyclePosition = d2.cyclePosition
             AND d1.donationType IN (:types)
             AND d2.donationType IN (:types)'
        )->setParameter('types', ['direct', 'registration', 'referral_placement'])
            ->getSingleScalarResult();

        if ($duplicates > 0) {
            $issues[] = "Found {$duplicates} duplicate positions in flower matrices";
        }

        // Check for users without proper referral placement
        $misplacedReferrals = $this->entityManager->createQuery(
            'SELECT COUNT(u.id)
             FROM App\Entity\User u
             LEFT JOIN App\Entity\Donation d WITH d.recipient = u
             AND d.donationType = :type
             WHERE u.referrer IS NOT NULL
             AND d.id IS NULL'
        )->setParameter('type', 'referral_placement')
            ->getSingleScalarResult();

        if ($misplacedReferrals > 0) {
            $issues[] = "Found {$misplacedReferrals} users without proper referral placement";
        }

        return $issues;
    }

    private function checkDonationProgress(): array
    {
        $issues = [];

        // Check for inconsistent cycle positions using native SQL
        $conn = $this->entityManager->getConnection();

        $sql = <<<SQL
            WITH user_cycles AS (
                SELECT 
                    u.id,
                    COUNT(d.id) as donation_count,
                    (COUNT(d.id)::decimal / 4) as cycle_ratio
                FROM "user" u
                JOIN donation d ON d.recipient_id = u.id
                WHERE d.donation_type IN ('direct', 'registration')
                GROUP BY u.id
            )
            SELECT COUNT(*) 
            FROM user_cycles 
            WHERE cycle_ratio != FLOOR(cycle_ratio)
        SQL;

        $inconsistentCount = (int) $conn->executeQuery($sql)->fetchOne();

        if ($inconsistentCount > 0) {
            $issues[] = sprintf(
                "Found %d users with incomplete donation cycles",
                $inconsistentCount
            );
        }

        // Check for incorrect progression using native SQL
        $progressSql = <<<SQL
            WITH user_progress AS (
                SELECT 
                    u.id,
                    u.current_flower_id,
                    COUNT(d.id) as donation_count,
                    f.level as current_level,
                    EXISTS (
                        SELECT 1 
                        FROM flower f2 
                        WHERE f2.level > f.level
                    ) as has_next_flower
                FROM "user" u
                JOIN donation d ON d.recipient_id = u.id
                JOIN flower f ON f.id = u.current_flower_id
                WHERE d.donation_type IN ('direct', 'registration')
                GROUP BY u.id, u.current_flower_id, f.level
            )
            SELECT COUNT(*) 
            FROM user_progress 
            WHERE donation_count >= 4 
            AND has_next_flower = false
        SQL;

        $incorrectProgress = (int) $conn->executeQuery($progressSql)->fetchOne();

        if ($incorrectProgress > 0) {
            $issues[] = sprintf(
                "Found %d users with incorrect flower progression",
                $incorrectProgress
            );
        }

        return $issues;
    }

    private function checkSolidaritySystem(): array
    {
        $issues = [];

        // Check for missing solidarity distributions using native SQL for complex calculations
        $conn = $this->entityManager->getConnection();

        $sql = <<<SQL
            WITH user_donations AS (
                SELECT 
                    u.id,
                    COALESCE(SUM(CASE 
                        WHEN d.donation_type IN ('direct', 'registration') 
                        THEN d.amount 
                        ELSE 0 
                    END), 0) * 0.5 as expected_solidarity,
                    COALESCE(SUM(CASE 
                        WHEN d.donation_type = 'solidarity' 
                        THEN d.amount 
                        ELSE 0 
                    END), 0) as actual_solidarity
                FROM "user" u
                LEFT JOIN donation d ON d.donor_id = u.id
                GROUP BY u.id
            )
            SELECT COUNT(*)
            FROM user_donations
            WHERE actual_solidarity < expected_solidarity
            AND expected_solidarity > 0
        SQL;

        $missingCount = (int) $conn->executeQuery($sql)->fetchOne();

        if ($missingCount > 0) {
            $issues[] = sprintf(
                "Found %d users with incorrect solidarity distribution",
                $missingCount
            );
        }

        return $issues;
    }

    private function fixInconsistentUserStates(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->text('Checking for inconsistent user states...');

        $inconsistentUsers = $this->entityManager->createQuery(
            'SELECT u
             FROM App\Entity\User u
             WHERE u.registrationPaymentStatus = :status
             AND (u.currentFlower IS NULL OR u.waitingSince IS NOT NULL)'
        )->setParameter('status', 'completed')
            ->getResult();

        foreach ($inconsistentUsers as $user) {
            if ($isDryRun) {
                $io->text(sprintf(
                    'Would fix inconsistent state for user %s',
                    $user->getEmail()
                ));
                continue;
            }

            // Fix the user state
            if ($user->getRegistrationPaymentStatus() === 'completed') {
                $violette = $this->flowerRepository->findOneBy(['name' => 'Violette']);
                if ($violette && !$user->getCurrentFlower()) {
                    $user->setCurrentFlower($violette);
                }
                if ($user->getWaitingSince()) {
                    $user->setWaitingSince(null);
                }
                $this->entityManager->persist($user);
            }
        }

        if (!$isDryRun && count($inconsistentUsers) > 0) {
            $this->entityManager->flush();
        }
    }

    private function handleVerificationResults(SymfonyStyle $io, array $results): int
    {
        if (empty($results['errors']) && empty($results['warnings'])) {
            $io->success('System verification completed. No issues found.');
            return Command::SUCCESS;
        }

        // Display errors if any
        if (!empty($results['errors'])) {
            $io->error('System verification found the following issues:');

            foreach ($results['errors'] as $category => $issues) {
                $io->section(ucfirst($category) . ' Issues:');
                foreach ($issues as $issue) {
                    $io->text('- ' . $issue);
                }
            }
        }

        // Display warnings if any
        if (!empty($results['warnings'])) {
            $io->warning('System verification found the following warnings:');

            foreach ($results['warnings'] as $category => $warnings) {
                $io->section(ucfirst($category) . ' Warnings:');
                foreach ($warnings as $warning) {
                    $io->text('- ' . $warning);
                }
            }
        }

        // Return failure if there are errors, success if only warnings
        return empty($results['errors']) ? Command::SUCCESS : Command::FAILURE;
    }

    private function fixOrphanedDonations(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->text('Checking for orphaned donations...');

        // Find orphaned donations
        $orphanedDonations = $this->entityManager->createQuery(
            'SELECT d FROM App\Entity\Donation d
             WHERE d.donor IS NULL OR d.recipient IS NULL'
        )->getResult();

        if (empty($orphanedDonations)) {
            $io->text('No orphaned donations found.');
            return;
        }

        foreach ($orphanedDonations as $donation) {
            if ($isDryRun) {
                $io->text(sprintf(
                    'Would remove orphaned donation ID %d (Amount: %.2f€)',
                    $donation->getId(),
                    $donation->getAmount()
                ));
                continue;
            }

            $this->entityManager->remove($donation);
        }

        if (!$isDryRun && !empty($orphanedDonations)) {
            $this->entityManager->flush();
            $io->success(sprintf('Removed %d orphaned donations', count($orphanedDonations)));
        }
    }

    private function fixInvalidFlowerAssignments(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->text('Checking for invalid flower assignments...');

        // Find users with completed registration but no flower
        $invalidUsers = $this->entityManager->createQuery(
            'SELECT u FROM App\Entity\User u
             WHERE u.currentFlower IS NULL
             AND u.registrationPaymentStatus = :status'
        )->setParameter('status', 'completed')
            ->getResult();

        if (empty($invalidUsers)) {
            $io->text('No invalid flower assignments found.');
            return;
        }

        // Get Violette flower (first flower)
        $violette = $this->flowerRepository->findOneBy(['name' => 'Violette']);
        if (!$violette) {
            throw new \RuntimeException('Violette flower not found in database');
        }

        foreach ($invalidUsers as $user) {
            if ($isDryRun) {
                $io->text(sprintf(
                    'Would assign user %s to Violette flower',
                    $user->getEmail()
                ));
                continue;
            }

            $user->setCurrentFlower($violette);
            $this->entityManager->persist($user);

            // Create initial donation record if missing
            $existingRegistration = $this->donationRepository->findOneBy([
                'recipient' => $user,
                'donationType' => 'registration'
            ]);

            if (!$existingRegistration) {
                $donation = new Donation();
                $donation
                    ->setDonationType('registration')
                    ->setDonor($user)
                    ->setRecipient($user)
                    ->setAmount(25.00)
                    ->setFlower($violette)
                    ->setTransactionDate($user->getCreatedAt() ?? new \DateTimeImmutable())
                    ->setCyclePosition(1);

                $this->entityManager->persist($donation);
            }
        }

        if (!$isDryRun && !empty($invalidUsers)) {
            $this->entityManager->flush();
            $io->success(sprintf('Fixed flower assignments for %d users', count($invalidUsers)));
        }
    }

    private function fixDonationProgress(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Fixing Donation Progress');

        // Get native database connection for complex queries
        $conn = $this->entityManager->getConnection();

        // Find users with incorrect cycle counts
        $sql = <<<SQL
            WITH user_cycles AS (
                SELECT 
                    u.id,
                    u.email,
                    u.current_flower_id,
                    COUNT(d.id) as donation_count
                FROM "user" u
                JOIN donation d ON d.recipient_id = u.id
                WHERE d.donation_type IN ('direct', 'registration')
                GROUP BY u.id, u.email, u.current_flower_id
                HAVING COUNT(d.id) >= 4
            )
            SELECT 
                uc.*,
                f.name as flower_name,
                EXISTS (
                    SELECT 1 FROM flower f2 WHERE f2.level > f.level
                ) as has_next_flower
            FROM user_cycles uc
            JOIN flower f ON f.id = uc.current_flower_id
            WHERE (uc.donation_count % 4) != 0
            OR (uc.donation_count >= 4 AND NOT EXISTS (
                SELECT 1 FROM flower f2 WHERE f2.level > f.level
            ))
        SQL;

        $incorrectProgression = $conn->executeQuery($sql)->fetchAllAssociative();

        if (empty($incorrectProgression)) {
            $io->text('No donation progress issues found.');
            return;
        }

        foreach ($incorrectProgression as $user) {
            if ($isDryRun) {
                $io->text(sprintf(
                    'Would fix progression for user %s (Donations: %d, Current Flower: %s)',
                    $user['email'],
                    $user['donation_count'],
                    $user['flower_name']
                ));
                continue;
            }

            try {
                $this->entityManager->beginTransaction();

                $userEntity = $this->userRepository->find($user['id']);
                if (!$userEntity) {
                    continue;
                }

                // Fix cycle completion and progression
                $this->progressionService->checkAndProcessProgression($userEntity);

                $this->entityManager->flush();
                $this->entityManager->commit();

                $io->text(sprintf(
                    'Fixed progression for user %s',
                    $userEntity->getEmail()
                ));

            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $io->error(sprintf(
                    'Error fixing progression for user %s: %s',
                    $user['email'],
                    $e->getMessage()
                ));
            }
        }

        // Fix users with inconsistent progression states
        $this->fixInconsistentProgressionStates($io, $isDryRun);

        $io->text('Donation progress fixes completed');
    }

    private function fixInconsistentProgressionStates(SymfonyStyle $io, bool $isDryRun): void
    {
        $sql = <<<SQL
            WITH user_progress AS (
                SELECT 
                    u.id,
                    u.email,
                    f.name as current_flower,
                    COUNT(d.id) as completed_donations,
                    (
                        SELECT COUNT(DISTINCT f2.id)
                        FROM flower f2
                        WHERE f2.level < f.level
                    ) as expected_completed_flowers
                FROM "user" u
                JOIN flower f ON f.id = u.current_flower_id
                LEFT JOIN donation d ON d.recipient_id = u.id
                WHERE d.donation_type IN ('direct', 'registration')
                GROUP BY u.id, u.email, f.name, f.level
            )
            SELECT *
            FROM user_progress
            WHERE completed_donations / 4 != expected_completed_flowers
        SQL;

        $inconsistentStates = $this->entityManager->getConnection()
            ->executeQuery($sql)
            ->fetchAllAssociative();

        foreach ($inconsistentStates as $state) {
            if ($isDryRun) {
                $io->text(sprintf(
                    'Would fix inconsistent state for user %s (Current: %s, Completed: %d)',
                    $state['email'],
                    $state['current_flower'],
                    $state['completed_donations']
                ));
                continue;
            }

            try {
                $this->entityManager->beginTransaction();

                $user = $this->userRepository->find($state['id']);
                if (!$user) {
                    continue;
                }

                // Reset to correct flower based on completed donations
                $correctFlower = $this->determineCorrectFlower($user);
                if ($correctFlower) {
                    $user->setCurrentFlower($correctFlower);
                    $this->entityManager->persist($user);
                }

                $this->entityManager->flush();
                $this->entityManager->commit();

            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $io->error(sprintf(
                    'Error fixing state for user %s: %s',
                    $state['email'],
                    $e->getMessage()
                ));
            }
        }
    }

    private function determineCorrectFlower(User $user): ?Flower
    {
        $completedDonations = $this->donationRepository->findBy([
            'recipient' => $user,
            'donationType' => ['direct', 'registration']
        ]);

        $completedCycles = floor(count($completedDonations) / 4);
        $flowers = $this->flowerRepository->findBy([], ['level' => 'ASC']);

        return $flowers[$completedCycles] ?? $flowers[0];
    }

    private function verifyAndFixFlowerProgression(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Verifying and fixing flower progression');

        // Get all users and their progression states with fixed position counting
        $sql = <<<SQL
            WITH user_progression AS (
                SELECT 
                    u.id,
                    u.email,
                    u.current_flower_id,
                    f.name as current_flower_name,
                    f.level as current_level,
                    COUNT(DISTINCT d.id) as completed_donations,
                    FLOOR(COUNT(DISTINCT d.id) / 4.0) as completed_cycles,
                    COUNT(DISTINCT d.cycle_position) as distinct_positions
                FROM "user" u
                JOIN flower f ON f.id = u.current_flower_id
                LEFT JOIN donation d ON d.recipient_id = u.id
                    AND d.donation_type IN ('direct', 'registration')
                GROUP BY u.id, u.email, u.current_flower_id, f.name, f.level
            )
            SELECT 
                up.*,
                EXISTS (
                    SELECT 1 
                    FROM flower f2 
                    WHERE f2.level = up.current_level + 1
                ) as has_next_flower
            FROM user_progression up
            WHERE completed_cycles >= 1
            OR distinct_positions != completed_donations
        SQL;

        $progressionIssues = $this->entityManager->getConnection()
            ->executeQuery($sql)
            ->fetchAllAssociative();

        if (empty($progressionIssues)) {
            $io->text('No flower progression issues found.');
            return;
        }

        foreach ($progressionIssues as $issue) {
            if ($isDryRun) {
                $io->text(sprintf(
                    'Would fix progression for user %s (Current: %s, Cycles: %d, Positions: %d)',
                    $issue['email'],
                    $issue['current_flower_name'],
                    $issue['completed_cycles'],
                    $issue['distinct_positions']
                ));
                continue;
            }

            try {
                $this->entityManager->beginTransaction();

                $user = $this->userRepository->find($issue['id']);
                if (!$user) {
                    continue;
                }

                // First fix positions if needed
                if ($issue['distinct_positions'] != $issue['completed_donations']) {
                    $this->fixUserMatrixPositions($user, $issue['current_flower_id']);
                }

                // Then check and process progression
                $this->progressionService->checkAndProcessProgression($user);

                // Handle completed cycles
                if ($issue['completed_cycles'] >= 1 && $issue['has_next_flower']) {
                    $this->handleCompletedCycles($user, (int)$issue['completed_cycles']);
                }

                $this->entityManager->flush();
                $this->entityManager->commit();

                $io->text(sprintf('Fixed progression for user %s', $user->getEmail()));

            } catch (\Exception $e) {
                $this->entityManager->rollback();
                $io->error(sprintf(
                    'Error fixing progression for user %s: %s',
                    $issue['email'],
                    $e->getMessage()
                ));
            }
        }

        $io->text('Flower progression verification and fixes completed');
    }

    private function fixUserMatrixPositions(User $user, int $flowerId): void
    {
        $flower = $this->flowerRepository->find($flowerId);
        if (!$flower) {
            return;
        }

        // Get all donations in current flower
        $donations = $this->donationRepository->findBy([
            'recipient' => $user,
            'flower' => $flower,
            'donationType' => ['direct', 'registration']
        ], ['transactionDate' => 'ASC']);

        // Reset and reassign positions
        foreach ($donations as $index => $donation) {
            $position = $index + 1;
            $matrixNumber = floor(($position - 1) / 16);
            
            $donation->setCyclePosition($position);
            $donation->setMatrixNumber($matrixNumber);
            $this->entityManager->persist($donation);
        }
    }

    private function handleCompletedCycles(User $user, int $completedCycles): void
    {
        $currentFlower = $user->getCurrentFlower();
        if (!$currentFlower) {
            return;
        }

        // Find next flower
        $nextFlower = $this->flowerRepository->findNextFlower($currentFlower);
        if (!$nextFlower) {
            return;
        }

        // Update user's flower if all cycles are complete
        if ($completedCycles >= 10) {
            $user->setCurrentFlower($nextFlower);
            $this->entityManager->persist($user);
        }

        // Follow referrer in next flower if applicable
        if ($user->getReferrer()) {
            $this->processReferralPlacement($user, $nextFlower);
        }
    }

    private function processReferralPlacement(User $user, Flower $flower): void
    {
        $referrer = $user->getReferrer();
        $position = $this->matrixPlacementService->findNextReferralPosition($referrer, $flower);

        if ($position) {
            $donation = new Donation();
            $donation
                ->setDonationType('referral_placement')
                ->setDonor($referrer)
                ->setRecipient($user)
                ->setFlower($flower)
                ->setCyclePosition($position)
                ->setTransactionDate(new \DateTimeImmutable())
                ->setAmount($flower->getDonationAmount());

            $this->entityManager->persist($donation);
        }
    }

    private function fixSolidarityMismatch(User $donor, float $expected, float $actual): void
    {
        $diff = $expected - $actual;
        if ($diff <= 0) {
            return;
        }

        // Find a valid recipient for the solidarity donation
        $recipient = $this->progressionService->findValidSolidarityRecipient();
        if (!$recipient) {
            return;
        }

        // Create solidarity donation with the missing amount
        $donation = new Donation();
        $donation
            ->setDonationType('solidarity')
            ->setDonor($donor)
            ->setRecipient($recipient)
            ->setAmount($diff)
            ->setTransactionDate(new \DateTimeImmutable())
            ->setFlower($recipient->getCurrentFlower())
            ->setSolidarityDistributionStatus(Donation::SOLIDARITY_STATUS_DISTRIBUTED);

        $this->entityManager->persist($donation);
    }

    private const MATRIX_SIZE = 16; // 4x4 matrix
    private const DONATIONS_PER_CYCLE = 4;

    private function fixMatrixStructure(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Fixing Matrix Structure');

        // Process each flower level
        $flowers = $this->flowerRepository->findBy([], ['level' => 'ASC']);
        foreach ($flowers as $flower) {
            $users = $this->userRepository->findByCurrentFlower($flower);
            
            // Group users by matrices (16 users per matrix)
            $matrices = array_chunk($users, self::MATRIX_SIZE);
            
            foreach ($matrices as $matrixIndex => $matrixUsers) {
                if ($isDryRun) {
                    $io->text(sprintf(
                        'Would fix matrix %d for flower %s (%d users)',
                        $matrixIndex + 1,
                        $flower->getName(),
                        count($matrixUsers)
                    ));
                    continue;
                }

                $this->rebuildMatrix($flower, $matrixUsers, $matrixIndex);
            }
        }
    }

    private function processCycleDonations(User $user, array $donations, bool $isDryRun, SymfonyStyle $io): void
    {
        // Sort donations by date
        usort($donations, fn($a, $b) => $a->getTransactionDate() <=> $b->getTransactionDate());
        
        // Group into cycles of 4
        $cycles = array_chunk($donations, self::DONATIONS_PER_CYCLE);
        
        foreach ($cycles as $cycleIndex => $cycleDonations) {
            if (count($cycleDonations) === self::DONATIONS_PER_CYCLE) {
                $totalAmount = array_reduce(
                    $cycleDonations,
                    fn($sum, $donation) => $sum + $donation->getAmount(),
                    0
                );
                
                $expectedSolidarity = $totalAmount * 0.5;
                
                // Check if solidarity donation exists
                $solidarityDonation = $this->donationRepository->findSolidarityDonation(
                    $user,
                    $cycleDonations[0]->getFlower(),
                    $cycleIndex + 1
                );
                
                if (!$solidarityDonation && !$isDryRun) {
                    $this->createSolidarityDonation($user, $expectedSolidarity);
                }
            }
        }
    }

    private function rebuildMatrix(Flower $flower, array $users, int $matrixIndex): void
    {
        $position = 1;
        foreach ($users as $user) {
            // Find earliest donation needing position
            $donation = $this->donationRepository->findEarliestDonationWithoutPosition($user, $flower);
            
            if ($donation) {
                $donation->setCyclePosition($position);
                $donation->setMatrixNumber($matrixIndex);
                $this->entityManager->persist($donation);
            }
            
            $position++;
        }
        
        $this->entityManager->flush();
    }
}