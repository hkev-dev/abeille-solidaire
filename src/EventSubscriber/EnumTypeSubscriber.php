<?php

namespace App\EventSubscriber;

use App\Doctrine\DBAL\Type\AbstractEnumType;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\Events;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EnumTypeSubscriber implements EventSubscriberInterface
{
    public function __construct()
    {
        $x = 'y';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::onMigrationsMigrating => 'onMigrationsMigrating',
            'postGenerateSchema' => 'onMigrationsMigrating',
            Events::onMigrationsMigrated => 'onMigrationsMigrating',
        ];
    }

    public function onMigrationsMigrating(GenerateSchemaEventArgs $eventArgs)
    {
        $schema = $eventArgs->getSchema();
        $em = $eventArgs->getEntityManager();
        $connection = $em->getConnection();

        // Parcourir tous les types Doctrine enregistrés
        foreach (Type::getTypesMap() as $typeName => $typeClass) {
            $type = Type::getType($typeName);

            // Vérifier si le type est une instance de votre AbstractEnumType
            if ($type instanceof AbstractEnumType) {
                $enumClass = $type::getEnumsClass();
                $enumValues = array_map(
                    fn ($case) => "'{$case->value}'",
                    $enumClass::cases()
                );

                // Vérifier si le type existe déjà
                $typeExists = $connection->fetchOne(
                    "SELECT 1 FROM pg_type WHERE typname = :typeName",
                    ['typeName' => $typeName]
                );

                // Si le type n'existe pas, le créer
                if (!$typeExists) {
                    $sql = sprintf(
                        'CREATE TYPE %s AS ENUM (%s)',
                        $typeName,
                        implode(', ', $enumValues)
                    );

                    // Ajouter l'instruction SQL au schéma
                    $connection->executeStatement($sql);
                }
            }
        }
    }
}
