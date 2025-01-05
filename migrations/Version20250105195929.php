<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250105195929 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add amount column to membership table';
    }

    public function up(Schema $schema): void
    {
        // First add the column as nullable
        $this->addSql('ALTER TABLE membership ADD amount NUMERIC(10, 2) DEFAULT NULL');
        
        // Update existing records with the default amount (25.00)
        $this->addSql('UPDATE membership SET amount = 25.00');
        
        // Then make the column not nullable
        $this->addSql('ALTER TABLE membership ALTER COLUMN amount SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE membership DROP amount');
    }
}
