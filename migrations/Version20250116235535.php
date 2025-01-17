<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250116235535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status column to membership table and modify has_paid_annual_fee';
    }

    public function up(Schema $schema): void
    {
        // First add the column as nullable
        $this->addSql('ALTER TABLE membership ADD status VARCHAR(20)');
        
        // Update existing records to have a status
        $this->addSql("UPDATE membership SET status = 'active' WHERE end_date > CURRENT_TIMESTAMP");
        $this->addSql("UPDATE membership SET status = 'expired' WHERE end_date <= CURRENT_TIMESTAMP");
        $this->addSql("UPDATE membership SET status = 'pending' WHERE status IS NULL");
        
        // Now make the column non-nullable
        $this->addSql('ALTER TABLE membership ALTER COLUMN status SET NOT NULL');
        
        // Modify has_paid_annual_fee
        $this->addSql('ALTER TABLE "user" ALTER has_paid_annual_fee DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE membership DROP status');
        $this->addSql('ALTER TABLE "user" ALTER has_paid_annual_fee SET DEFAULT false');
    }
}
