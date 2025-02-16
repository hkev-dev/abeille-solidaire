<?php

namespace App\Doctrine\DBAL\Type\Enum;

use App\Constant\Enum\Project\State;
use App\Doctrine\DBAL\Type\AbstractEnumType;

class ProjectStateEnumType extends AbstractEnumType
{
    public const string NAME = 'project_state_enum_type';

    public static function getEnumsClass(): string
    {
        return State::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}