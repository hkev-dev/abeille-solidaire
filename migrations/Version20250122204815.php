<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250122204815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add donation_type column to&_ donation table';
    }

    public function up(Schema $schema): void
    {
        // Add the column as nullable first
        $this->addSql('ALTER TABLE donation ADD donation_type VARCHAR(20)');
        
        // Update existing records
        $this->addSql("UPDATE donation SET donation_type = 'registration'");
        
        // Now make it NOT NULL
        $this->addSql('ALTER TABLE donation ALTER COLUMN donation_type SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE donation DROP donation_type');
    }
}
