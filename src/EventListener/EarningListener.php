<?php

namespace App\EventListener;


use App\Entity\Earning;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Earning::class)]
readonly class EarningListener
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function postPersist(Earning $earning): void
    {
        if ($project = $earning->getBeneficiary()->getDonor()->getCurrentProject()) {
            try {
                $project->addPledged($earning->getAmount());
            } catch (\Exception $e) {
                $this->logger->error('Can not add pledged amount to project', ['exception' => $e]);
            }
        }
    }
}