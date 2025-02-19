<?php

namespace App\EventListener;


use App\Entity\Project;
use App\Service\ProjectService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: Project::class)]
readonly class ProjectListener
{
    public function __construct(private ProjectService $projectService)
    {
    }

    public function postUpdate(Project $project): void
    {
        $this->projectService->processProjectCompletion($project);
    }
}