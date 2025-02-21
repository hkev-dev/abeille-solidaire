<?php

namespace App\Command\Cron;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'cron:delete-unactivated-user',
    description: 'Add a short description for your command',
)]
class CronDeleteUnactivatedUserCommand extends Command
{
    public function __construct(private readonly UserRepository $userRepository, private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->success('Begin Cron Delete Unactivated User');

        $dateThreshold = new DateTime('-30 days');

        // Fetch users who have no donations or whose donations are unpaid and older than 30 days
        $toDeleteUsers = $this->userRepository->findUnactivatedUsers($dateThreshold);

        foreach ($toDeleteUsers as $user) {
            $this->entityManager->remove($user);
        }

        $this->entityManager->flush();
        $io->info(count($toDeleteUsers) . ' unactivated users deleted.');
        
        $io->success('End Cron Delete Unactivated User');
        return Command::SUCCESS;
    }
}
