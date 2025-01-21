<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250121194010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_method field to donation and membership tables';
    }

    public function up(Schema $schema): void
    {
        // Add columns
        $this->addSql('ALTER TABLE donation ADD payment_method VARCHAR(20)');
        $this->addSql('ALTER TABLE membership ADD payment_method VARCHAR(20)');

        // Set default values for existing records
        $this->addSql('UPDATE donation SET payment_method = \'stripe\' WHERE payment_method IS NULL');
        $this->addSql('UPDATE membership SET payment_method = \'stripe\' WHERE payment_method IS NULL');

        // Make columns NOT NULL after setting defaults
        $this->addSql('ALTER TABLE donation ALTER COLUMN payment_method SET NOT NULL');
        $this->addSql('ALTER TABLE membership ALTER COLUMN payment_method SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE membership DROP payment_method');
        $this->addSql('ALTER TABLE donation DROP payment_method');
    }
}
