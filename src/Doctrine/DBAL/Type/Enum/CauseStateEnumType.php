<?php

namespace App\Doctrine\DBAL\Type\Enum;

use App\Constant\Enum\Cause\State;
use App\Doctrine\DBAL\Type\AbstractEnumType;

class CauseStateEnumType extends AbstractEnumType
{
    public const string NAME = 'cause_state_enum_type';

    public static function getEnumsClass(): string
    {
        return State::class;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}