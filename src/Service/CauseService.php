<?php

namespace App\Service;

use App\Entity\Cause;
use App\Repository\CauseRepository;

readonly class CauseService
{
    public function __construct(private CauseRepository $causeRepository)
    {
    }

    public function processCauseProgress(Cause $cause, float $amount): void
    {
        /*if ($cause->getStatus() !== State::IN_PROGRESS || $cause->getPledged() < $cause->getGoal()) {
            return;
        }*/

        $cause
            ->setPledged($cause->getPledged() + $amount);

        $this->causeRepository->save($cause, true);
    }
}