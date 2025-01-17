<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250116210853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add annual fee tracking fields to user table';
    }

    public function up(Schema $schema): void
    {
        // First add the columns as nullable
        $this->addSql('ALTER TABLE "user" ADD has_paid_annual_fee BOOLEAN DEFAULT false');
        $this->addSql('ALTER TABLE "user" ADD annual_fee_paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        
        // Update existing records to have has_paid_annual_fee = false
        $this->addSql('UPDATE "user" SET has_paid_annual_fee = false WHERE has_paid_annual_fee IS NULL');
        
        // Now make has_paid_annual_fee non-nullable
        $this->addSql('ALTER TABLE "user" ALTER COLUMN has_paid_annual_fee SET NOT NULL');
        
        // Clean up transaction-related tables
        $this->addSql('DROP SEQUENCE IF EXISTS transaction_id_seq CASCADE');
        $this->addSql('ALTER TABLE transaction DROP CONSTRAINT IF EXISTS fk_723705d1a76ed395');
        $this->addSql('DROP TABLE IF EXISTS transaction');
        $this->addSql('ALTER TABLE donation DROP CONSTRAINT IF EXISTS fk_31e581a0449747c7');
        $this->addSql('DROP INDEX IF EXISTS idx_31e581a0449747c7');
        $this->addSql('ALTER TABLE donation DROP COLUMN IF EXISTS source_flower_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE transaction_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE transaction (id SERIAL NOT NULL, user_id INT NOT NULL, type VARCHAR(10) NOT NULL, amount NUMERIC(10, 2) NOT NULL, balance_after NUMERIC(10, 2) NOT NULL, description TEXT DEFAULT NULL, reference VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_723705d1a76ed395 ON transaction (user_id)');
        $this->addSql('COMMENT ON COLUMN transaction.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT fk_723705d1a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE donation ADD source_flower_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT fk_31e581a0449747c7 FOREIGN KEY (source_flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_31e581a0449747c7 ON donation (source_flower_id)');
        $this->addSql('ALTER TABLE "user" DROP has_paid_annual_fee');
        $this->addSql('ALTER TABLE "user" DROP annual_fee_paid_at');
    }
}
