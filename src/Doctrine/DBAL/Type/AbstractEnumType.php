<?php

namespace App\Doctrine\DBAL\Type;

use BackedEnum;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use LogicException;

abstract class AbstractEnumType extends Type
{
    protected string $schema = "public";

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $this->schema . ".\"" . $this->getName() . "\"";
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return null;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if (false === enum_exists($this::getEnumsClass())) {
            throw new LogicException("Class {$this::getEnumsClass()} should be an enum");
        }

        return $this::getEnumsClass()::tryFrom($value);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function getMappedDatabaseTypes(AbstractPlatform $platform): array
    {
        return [
            $this->getSQLDeclaration([], $platform) => $this->getName()
        ];
    }

    abstract public static function getEnumsClass(): string;
}
