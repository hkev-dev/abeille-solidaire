<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250104171429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add new user fields with default values for existing users';
    }

    public function up(Schema $schema): void
    {
        // First add the columns as nullable
        $this->addSql('ALTER TABLE "user" ADD username VARCHAR(50) NULL');
        $this->addSql('ALTER TABLE "user" ADD phone VARCHAR(20) NULL');
        $this->addSql('ALTER TABLE "user" ADD country VARCHAR(2) NULL');
        $this->addSql('ALTER TABLE "user" ADD account_type VARCHAR(20) NULL');
        $this->addSql('ALTER TABLE "user" ADD organization_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD organization_number VARCHAR(50) DEFAULT NULL');

        // Update existing records with default values
        $this->addSql('UPDATE "user" SET 
            username = CONCAT(\'user_\', id),
            phone = \'+33100000000\',
            country = \'FR\',
            account_type = \'PRIVATE\'
            WHERE username IS NULL');

        // Now make the columns NOT NULL
        $this->addSql('ALTER TABLE "user" ALTER COLUMN username SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN phone SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN country SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN account_type SET NOT NULL');

        // Add unique constraint for username
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649F85E0677 ON "user" (username)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_8D93D649F85E0677');
        $this->addSql('ALTER TABLE "user" DROP username');
        $this->addSql('ALTER TABLE "user" DROP phone');
        $this->addSql('ALTER TABLE "user" DROP country');
        $this->addSql('ALTER TABLE "user" DROP account_type');
        $this->addSql('ALTER TABLE "user" DROP organization_name');
        $this->addSql('ALTER TABLE "user" DROP organization_number');
    }
}
