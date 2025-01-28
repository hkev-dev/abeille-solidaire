<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Flower;
use App\Entity\Donation;
use App\Service\MatrixService;
use App\Service\FlowerService;
use App\Service\DonationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:correct-data',
    description: 'Correct matrix, flower, and donation data'
)]
class DataCorrectionCommand extends Command
{
    private EntityManagerInterface $em;
    private MatrixService $matrixService;
    private FlowerService $flowerService;
    private DonationService $donationService;
    private LoggerInterface $logger;
    private SymfonyStyle $io;
    private bool $isDryRun = false;

    public function __construct(
        EntityManagerInterface $em,
        MatrixService $matrixService,
        FlowerService $flowerService,
        DonationService $donationService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->em = $em;
        $this->matrixService = $matrixService;
        $this->flowerService = $flowerService;
        $this->donationService = $donationService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Run in dry-run mode (no changes will be made)')
            ->addOption('matrix', null, InputOption::VALUE_NONE, 'Fix matrix structure only')
            ->addOption('flowers', null, InputOption::VALUE_NONE, 'Fix flower progression only')
            ->addOption('donations', null, InputOption::VALUE_NONE, 'Fix donations only')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Fix everything (default if no options specified)');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->isDryRun = $input->getOption('dry-run');

        if ($this->isDryRun) {
            $this->io->note('Running in dry-run mode - no changes will be made');
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Starting data correction process');

        try {
            $fixMatrix = $input->getOption('matrix');
            $fixFlowers = $input->getOption('flowers');
            $fixDonations = $input->getOption('donations');
            $fixAll = $input->getOption('all') || (!$fixMatrix && !$fixFlowers && !$fixDonations);

            if ($fixAll || $fixMatrix) {
                $this->correctMatrixStructure();
            }

            if ($fixAll || $fixFlowers) {
                $this->correctFlowerProgression();
            }

            if ($fixAll || $fixDonations) {
                $this->correctDonations();
            }

            if (!$this->isDryRun) {
                $this->em->flush();
                $this->io->success('All corrections have been applied successfully');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error('Error during data correction: ' . $e->getMessage());
            $this->io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    private function correctMatrixStructure(): void
    {
        try {
            $this->doCorrectMatrixStructure();
        } catch (\Exception $e) {
            $this->logger->error('Error in matrix correction: ' . $e->getMessage());
            $this->io->error($e->getMessage());
        }
    }

    private function doCorrectMatrixStructure(): void
    {
        $this->io->section('Correcting Matrix Structure');

        $this->io->comment('Fetching users...');
        
        // First, get all completed users ordered by creation date
        $users = $this->em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('u, p')
            ->leftJoin('u.parent', 'p')
            ->where('u.registrationPaymentStatus = :status')
            ->setParameter('status', 'completed')
            ->orderBy('u.createdAt', 'ASC')
            ->getQuery()
            ->getResult();

        $total = count($users);

        if ($total === 0) {
            $this->io->warning('No users found to process');
            return;
        }

        $this->io->progressStart($total);

        $processedCount = 0;
        foreach ($users as $user) {
            $this->io->progressAdvance();
            $oldDepth = $user->getMatrixDepth();
            $oldPosition = $user->getMatrixPosition();

            // Calculate correct matrix depth
            $depth = 0;
            $parent = $user->getParent();
            while ($parent) {
                $depth++;
                $parent = $parent->getParent();
            }

            // Calculate matrix position based on creation order within parent's children
            $parent = $user->getParent();

            if (!$parent) {
                continue; // Skip root user
            }

            // Calculate matrix position by counting earlier siblings
            $position = $this->em->createQueryBuilder()
                ->select('COUNT(s.id)')
                ->from(User::class, 's')
                ->where('s.parent = :parent')
                ->andWhere('s.createdAt < :created')
                ->andWhere('s.registrationPaymentStatus = :status')
                ->setParameter('parent', $parent)
                ->setParameter('created', $user->getCreatedAt())
                ->setParameter('status', 'completed')
                ->getQuery()
                ->getSingleScalarResult() + 1;

            // Verify position is within matrix bounds (1-4)
            if ($position > 4) {
                $this->logger->warning(sprintf(
                    'Invalid matrix position detected for user %s: position %d exceeds maximum of 4',
                    $user->getEmail(),
                    $position
                ));
                continue;
            }

            // Get total siblings for debugging
            $totalSiblings = $this->em->createQueryBuilder()
                ->select('COUNT(s.id)')
                ->from(User::class, 's')
                ->where('s.parent = :parent')
                ->andWhere('s.registrationPaymentStatus = :status')
                ->setParameter('parent', $parent)
                ->setParameter('status', 'completed')
                ->getQuery()
                ->getSingleScalarResult();

            if ($this->isDryRun) {
                $this->io->writeln('');  // New line before message
                $this->io->text(sprintf(
                    '  Checking User %s (Current: Depth=%d, Pos=%d)',
                    $user->getEmail(),
                    $oldDepth,
                    $oldPosition
                ));
                $this->io->text(sprintf(
                    '  Calculated: Depth=%d, Pos=%d (among %d siblings)',
                    $depth,
                    $position,
                    $totalSiblings
                ));
            }

            if (($oldDepth !== $depth || $oldPosition !== $position) && $position <= 4) {
                if ($this->isDryRun) {
                    $this->io->writeln('');  // New line before the message
                    $this->io->text(sprintf(
                        '  [WOULD CHANGE] User %s: Depth %d→%d, Position %d→%d',
                        $user->getEmail(),
                        $oldDepth,
                        $depth,
                        $oldPosition,
                        $position
                    ));
                } else {
                    $user->setMatrixDepth($depth)
                        ->setMatrixPosition($position);
                }
                $processedCount++;
            }
        }

        $this->io->progressFinish();
        $this->io->newLine();
        
        if ($processedCount > 0) {
            $message = $this->isDryRun
                ? sprintf('Found %d users that need correction', $processedCount)
                : sprintf('Successfully corrected %d users', $processedCount);
            $this->io->success($message);
        } else {
            $this->io->info('No corrections needed for matrix structure');
        }
    }

    private function correctFlowerProgression(): void
    {
        try {
            $this->doCorrectFlowerProgression();
        } catch (\Exception $e) {
            $this->logger->error('Error in flower progression correction: ' . $e->getMessage());
            $this->io->error($e->getMessage());
        }
    }

    private function doCorrectFlowerProgression(): void
    {
        $this->io->section('Correcting Flower Progression');

        $this->io->comment('Fetching users...');
        
        $query = $this->em->createQueryBuilder()
            ->select('u, p, f, pf')
            ->from(User::class, 'u')
            ->leftJoin('u.parent', 'p')
            ->leftJoin('u.currentFlower', 'f')
            ->leftJoin('p.currentFlower', 'pf')
            ->where('u.registrationPaymentStatus = :status')
            ->setParameter('status', 'completed')
            ->orderBy('u.matrixDepth', 'ASC')
            ->addOrderBy('u.id', 'ASC')
            ->getQuery();

        $users = $query->getResult();
        $total = count($users);

        if ($total === 0) {
            $this->io->warning('No users found to process');
            return;
        }

        $this->io->progressStart($total);
        $processedCount = 0;

        foreach ($users as $user) {
            $this->io->progressAdvance();
            $parent = $user->getParent();
            if (!$parent) {
                continue; // Skip root user
            }

            $currentFlower = $user->getCurrentFlower();
            $parentFlower = $parent->getCurrentFlower();
            $cycleProgress = $this->donationService->getCycleProgress($parent);

            if ($this->isDryRun) {
                $this->io->text(sprintf(
                    '  Parent %s Cycle Status: %d/4 donations, %d/4 children',
                    $parent->getEmail(),
                    $cycleProgress['donations'],
                    $cycleProgress['children']
                ));
            }

            // User should be in same flower as parent
            if ($currentFlower->getId() !== $parentFlower->getId() ||
                ($cycleProgress['isComplete'] && $currentFlower->getLevel() < $parentFlower->getLevel())) {
                if ($this->isDryRun) {
                    $this->io->writeln('');  // New line before the message
                    $this->io->text(sprintf(
                        '  [WOULD CHANGE] User %s: Flower %s (Level %d) → %s (Level %d)',
                        $user->getEmail(),
                        $currentFlower->getName(),
                        $currentFlower->getLevel(),
                        $parentFlower->getName(),
                        $parentFlower->getLevel()
                    ));
                } else {
                    $user->setCurrentFlower($parentFlower);
                }
                $processedCount++;
            }
        }

        $this->io->progressFinish();
        $this->io->newLine();
        
        if ($processedCount > 0) {
            $message = $this->isDryRun
                ? sprintf('Found %d users with incorrect flower levels', $processedCount)
                : sprintf('Successfully corrected %d users\' flower levels', $processedCount);
            $this->io->success($message);
        } else {
            $this->io->info('No corrections needed for flower progression');
        }
    }

    private function correctDonations(): void
    {
        try {
            $this->doCorrectDonations();
        } catch (\Exception $e) {
            $this->logger->error('Error in donations correction: ' . $e->getMessage());
            $this->io->error($e->getMessage());
        }
    }

    private function doCorrectDonations(): void
    {
        $this->io->section('Correcting Donations');

        $this->io->comment('Fetching donations...');
        
        // Get all registration donations
        $query = $this->em->createQueryBuilder()
            ->select('d, dr, rec, df')
            ->from(Donation::class, 'd')
            ->leftJoin('d.donor', 'dr')
            ->leftJoin('d.recipient', 'rec')
            ->leftJoin('d.flower', 'df')
            ->where('d.donationType = :type')
            ->andWhere('d.paymentStatus = :status')
            ->setParameter('type', Donation::TYPE_REGISTRATION)
            ->setParameter('status', 'completed')
            ->orderBy('d.transactionDate', 'ASC')
            ->getQuery();

        $donations = $query->getResult();
        $total = count($donations);

        if ($total === 0) {
            $this->io->warning('No donations found to process');
            return;
        }

        $this->io->progressStart($total);
        $processedCount = 0;

        foreach ($donations as $donation) {
            $this->io->progressAdvance();
            $donor = $donation->getDonor();
            $recipient = $donation->getRecipient();
            $flower = $donation->getFlower();

            // Registration donation should go to parent
            $correctRecipient = $donor->getParent();
            if (!$correctRecipient) {
                continue; // Skip if no parent (root user)
            }

            // Check recipient
            if ($recipient->getId() !== $correctRecipient->getId()) {
                if ($this->isDryRun) {
                    $this->io->writeln('');  // New line before the message
                    $this->io->text(sprintf(
                        '  [WOULD CHANGE] Donation recipient for %s: %s → %s',
                        $donor->getEmail(),
                        $recipient->getEmail(),
                        $correctRecipient->getEmail()
                    ));
                } else {
                    $donation->setRecipient($correctRecipient);
                }
                $processedCount++;
            }

            // Check flower level
            $correctFlower = $correctRecipient->getCurrentFlower();
            if ($flower->getId() !== $correctFlower->getId()) {
                if ($this->isDryRun) {
                    $this->io->writeln('');  // New line before the message
                    $this->io->text(sprintf(
                        '  [WOULD CHANGE] Donation flower for %s: %s → %s',
                        $donor->getEmail(),
                        $flower->getName(),
                        $correctFlower->getName()
                    ));
                } else {
                    $donation->setFlower($correctFlower);
                }
                $processedCount++;
            }
        }

        $this->io->progressFinish();
        $this->io->newLine();
        
        if ($processedCount > 0) {
            $message = $this->isDryRun
                ? sprintf('Found %d donations that need correction', $processedCount)
                : sprintf('Successfully corrected %d donations', $processedCount);
            $this->io->success($message);
        } else {
            $this->io->info('No corrections needed for donations');
        }
    }
}
