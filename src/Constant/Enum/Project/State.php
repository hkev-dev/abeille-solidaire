<?php

namespace App\Constant\Enum\Project;

enum State: string
{
    case COMPLETED = 'completed';
    case IN_PROGRESS = 'in_progress';
    case CANCELED = 'canceled';
}
