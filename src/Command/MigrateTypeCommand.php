<?php

namespace App\Command;

use App\Doctrine\DBAL\Type\AbstractEnumType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-type',
    description: 'Add a short description for your command',
)]
class MigrateTypeCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Create PostgreSQL ENUM types for custom Doctrine ENUM types.')
            ->setHelp('This command creates PostgreSQL ENUM types for all custom Doctrine ENUM types.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->em->getConnection();

        // Parcourir tous les types Doctrine enregistrés
        foreach (Type::getTypesMap() as $typeName => $typeClass) {
            $type = Type::getType($typeName);

            // Vérifier si le type est une instance de votre AbstractEnumType
            if ($type instanceof AbstractEnumType) {
                $enumClass = $type::getEnumsClass();
                $enumValues = array_map(
                    fn ($case) => $case->value,
                    $enumClass::cases()
                );

                // Vérifier si le type existe déjà
                $typeExists = $connection->fetchOne(
                    "SELECT 1 FROM pg_type WHERE typname = :typeName",
                    ['typeName' => $typeName]
                );

                $sql = sprintf(
                    'CREATE TYPE %s AS ENUM (%s)',
                    $typeName,
                    implode(', ', array_map(fn ($value) => "'$value'", $enumValues))
                );

                if (!$typeExists) {
                    // Exécuter la commande SQL
                    $connection->executeStatement($sql);

                    $io->success(sprintf('Created ENUM type: %s', $typeName));
                } else {
                    $currentValues = $this->getCurrentEnumValues($connection, $typeName);
                    // Comparer les valeurs actuelles avec les nouvelles valeurs
                    if (array_diff($currentValues, $enumValues) || array_diff($enumValues, $currentValues)) {
                        // Mettre à jour le type ENUM s'il y a des différences
                        $this->updateEnumType($connection, $typeName, $enumValues, $io);
                    } else {
                        $io->note(sprintf('ENUM type is up to date: %s', $typeName));
                    }
                }
            }
        }

        $io->success('All ENUM types have been processed.');

        return Command::SUCCESS;
    }

    private function getCurrentEnumValues($connection, string $typeName): array
    {
        // Récupérer les valeurs actuelles du type ENUM
        $sql = "SELECT enumlabel 
                FROM pg_enum 
                JOIN pg_type ON pg_enum.enumtypid = pg_type.oid 
                WHERE pg_type.typname = :typeName 
                ORDER BY enumsortorder";

        $result = $connection->fetchAllAssociative($sql, ['typeName' => $typeName]);

        // Extraire les valeurs
        return array_map(fn ($row) => $row['enumlabel'], $result);
    }

    private function updateEnumType($connection, string $typeName, array $newValues, SymfonyStyle $io): void
    {
        // 1. Renommer l'ancien type ENUM
        $tempTypeName = $typeName . '_old';
        $connection->executeStatement("ALTER TYPE $typeName RENAME TO $tempTypeName");

        // 2. Créer le nouveau type ENUM
        $sql = sprintf(
            'CREATE TYPE %s AS ENUM (%s)',
            $typeName,
            implode(', ', array_map(fn ($value) => "'$value'", $newValues))
        );
        $connection->executeStatement($sql);

        // 3. Mettre à jour les colonnes qui utilisent l'ancien type
        $tables = $connection->fetchAllAssociative(
            "SELECT table_name, column_name 
             FROM information_schema.columns 
             WHERE udt_name = :tempTypeName",
            ['tempTypeName' => $tempTypeName]
        );

        foreach ($tables as $table) {
            $tableName = $table['table_name'];
            $columnName = $table['column_name'];

            $connection->executeStatement(
                "ALTER TABLE $tableName 
                 ALTER COLUMN $columnName TYPE $typeName 
                 USING $columnName::text::$typeName"
            );
        }

        // 4. Supprimer l'ancien type ENUM
        $connection->executeStatement("DROP TYPE $tempTypeName");

        $io->success(sprintf('Updated ENUM type: %s', $typeName));
    }

}
