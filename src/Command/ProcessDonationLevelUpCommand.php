<?php

namespace App\Command;

use App\Entity\Donation;
use App\Repository\DonationRepository;
use App\Service\DonationService;
use App\Service\FlowerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-donation-level-up',
    description: 'Process pending cycle completions for users',
)]
class ProcessDonationLevelUpCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FlowerService          $flowerService,
        private readonly DonationRepository     $donationRepository, private readonly DonationService $donationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        try {
            // Get all users with completed registration
            /** @var Donation $donations */
            $donations = $this->em->createQueryBuilder()
                ->select('donation')
                ->from(Donation::class, 'donation')
                ->leftJoin('donation.flower', 'flower')
                ->andWhere('donation.flower IS NOT NULL')
                ->andWhere('donation.paymentStatus = :status')
                ->setParameter('status', Donation::PAYMENT_COMPLETED)
                ->orderBy('donation.paymentCompletedAt', 'DESC')
                ->orderBy('flower.level', 'ASC')
                ->getQuery()
                ->getResult();

            $processed = 0;
            foreach ($donations as $donation) {
                // Dispatch donation level up event
                if ($this->donationService->canLevelUp($donation)){
                    $donation->setFlower($this->flowerService->getNextFlower($donation->getFlower()));
                    $this->em->persist($donation);
                    $this->em->flush();
                }

                $processed++;
            }

            $io->success(sprintf('Successfully processed %d cycle completions', $processed));
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}