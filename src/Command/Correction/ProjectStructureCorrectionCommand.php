<?php

namespace App\Command\Correction;

use App\Constant\Enum\Project\State;
use App\Repository\ProjectRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'correction:project-structure',
    description: 'Add a short description for your command',
)]
class ProjectStructureCorrectionCommand extends Command
{
    public function __construct(private readonly ProjectRepository $projectRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->note('Correcting projects...');

        $projects = $this->projectRepository->findAll();

        foreach ($projects as $project) {
            $creator = $project->getCreator();
            $project->setPledged($creator->getReceivedAmount())
                ->setStatus(State::IN_PROGRESS)
            ;

            $creator->addProject($project);

            $this->projectRepository->save($project, true);
        }

        $io->success('Projects have been corrected!');

        return Command::SUCCESS;
    }
}
