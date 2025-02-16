<?php

namespace App\EventListener;


use App\Entity\Earning;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Earning::class)]
class EarningListener
{
    public function __construct()
    {
    }

    public function postPersist(Earning $earning): void
    {
        $project = $earning->getDonor()->getDonor()->getCurrentProject();
        $project->addPledged($earning->getAmount());
    }
}