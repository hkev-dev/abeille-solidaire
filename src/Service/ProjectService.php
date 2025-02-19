<?php

namespace App\Service;

use App\Constant\Enum\Project\State;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use DateTimeImmutable;

readonly class ProjectService
{
    public function __construct(private ProjectRepository $projectRepository)
    {
    }

    public function processProjectCompletion(Project $project): void
    {
        if ($project->getStatus() !== State::IN_PROGRESS || $project->getPledged() < $project->getGoal()) {
            return;
        }

        $project
            ->setStatus(State::COMPLETED)
            ->setIsActive(false)
            ->setCompletedAt(new DateTimeImmutable())
            ->setCreator(null);

        $this->projectRepository->save($project, true);
    }
}