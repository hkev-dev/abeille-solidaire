<?php

namespace App\Command;

use App\Entity\User;
use App\Service\DonationService;
use App\Service\MatrixService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-pending-cycles',
    description: 'Process pending cycle completions for users',
)]
class ProcessPendingCyclesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DonationService $donationService,
        private readonly MatrixService $matrixService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            // Get all users with completed registration
            $users = $this->em->createQueryBuilder()
                ->select('u')
                ->from(User::class, 'u')
                ->getQuery()
                ->getResult();

            $processed = 0;
            foreach ($users as $user) {
                if ($this->donationService->hasCompletedCycle($user)) {
                    $io->info(sprintf(
                        'Processing cycle completion for user %s (ID: %d)',
                        $user->getEmail(),
                        $user->getId()
                    ));

                    // Process the cycle completion
                    $this->matrixService->processUserCycleCompletion($user);
                    $processed++;

                    // Flush after each user to avoid memory issues
                    $this->em->flush();
                    $this->em->clear();
                }
            }

            $io->success(sprintf('Successfully processed %d cycle completions', $processed));
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}