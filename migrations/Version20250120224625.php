<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250120224625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status column to donation table';
    }

    public function up(Schema $schema): void
    {
        // Add status column as nullable first
        $this->addSql('ALTER TABLE donation ADD status VARCHAR(20)');
        
        // Update existing records with appropriate status
        $this->addSql("UPDATE donation SET status = 'completed' WHERE stripe_payment_intent_id IS NOT NULL OR coinpayments_transaction_id IS NOT NULL");
        $this->addSql("UPDATE donation SET status = 'pending' WHERE status IS NULL");
        
        // Now make it non-nullable
        $this->addSql('ALTER TABLE donation ALTER COLUMN status SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE donation DROP COLUMN status');
    }
}
