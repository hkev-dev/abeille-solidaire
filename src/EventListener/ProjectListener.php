<?php

namespace App\EventListener;


use App\Entity\Earning;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Project::class)]
class ProjectListener
{
    public function __construct()
    {
    }

    public function postUpdate(Project $project): void
    {

//        if ($project->getPledged() >= $project->getGoal()) {
//            $project->setIsActive(false);
//        }
//        $project = $earning->getDonor()->getDonor()->getCurrentProject();
//        $project->addPledged($earning->getAmount());
    }
}