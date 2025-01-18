<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250117214311 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add annual fee expiration date and pending status tracking';
    }

    public function up(Schema $schema): void
    {
        // First add nullable columns
        $this->addSql('ALTER TABLE "user" ADD annual_fee_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD is_annual_fee_pending BOOLEAN DEFAULT FALSE');
        
        // Update existing records
        $this->addSql('UPDATE "user" SET is_annual_fee_pending = FALSE WHERE is_annual_fee_pending IS NULL');
        
        // Now make is_annual_fee_pending NOT NULL
        $this->addSql('ALTER TABLE "user" ALTER COLUMN is_annual_fee_pending SET NOT NULL');
        
        // Add index for referral lookups
        $this->addSql('CREATE INDEX idx_user_referral_code ON "user" (referral_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_user_referral_code');
        $this->addSql('ALTER TABLE "user" DROP annual_fee_expires_at');
        $this->addSql('ALTER TABLE "user" DROP is_annual_fee_pending');
    }
}
