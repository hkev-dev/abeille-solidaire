<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250122190533 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE membership_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE flower_cycle_completion_id_seq CASCADE');
        $this->addSql('ALTER TABLE membership DROP CONSTRAINT fk_86ffd285a76ed395');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP CONSTRAINT fk_af5a2a3ca76ed395');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP CONSTRAINT fk_af5a2a3c2c09d409');
        $this->addSql('DROP TABLE membership');
        $this->addSql('DROP TABLE flower_cycle_completion');
        $this->addSql('ALTER TABLE donation DROP donation_type');
        $this->addSql('ALTER TABLE donation DROP cycle_position');
        $this->addSql('ALTER TABLE donation DROP stripe_payment_intent_id');
        $this->addSql('ALTER TABLE donation DROP coinpayments_transaction_id');
        $this->addSql('ALTER TABLE donation DROP crypto_currency');
        $this->addSql('ALTER TABLE donation DROP crypto_amount');
        $this->addSql('ALTER TABLE donation DROP exchange_rate');
        $this->addSql('ALTER TABLE donation DROP confirmations_needed');
        $this->addSql('ALTER TABLE donation DROP confirmations_received');
        $this->addSql('ALTER TABLE donation DROP status_url');
        $this->addSql('ALTER TABLE donation DROP destination_address');
        $this->addSql('ALTER TABLE donation DROP crypto_status');
        $this->addSql('ALTER TABLE donation DROP solidarity_distribution_status');
        $this->addSql('ALTER TABLE donation DROP status');
        $this->addSql('ALTER TABLE "user" DROP project_description');
        $this->addSql('ALTER TABLE "user" DROP is_annual_fee_pending');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE membership_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE flower_cycle_completion_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE membership (id SERIAL NOT NULL, user_id INT NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, coinpayments_txn_id VARCHAR(255) DEFAULT NULL, crypto_currency VARCHAR(20) DEFAULT NULL, crypto_amount NUMERIC(18, 8) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, amount NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, metadata JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_86ffd285a76ed395 ON membership (user_id)');
        $this->addSql('COMMENT ON COLUMN membership.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN membership.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE flower_cycle_completion (id SERIAL NOT NULL, user_id INT NOT NULL, flower_id INT NOT NULL, cycle_number INT NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, wallet_amount NUMERIC(10, 2) NOT NULL, solidarity_amount NUMERIC(10, 2) NOT NULL, cycle_positions JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_af5a2a3c2c09d409 ON flower_cycle_completion (flower_id)');
        $this->addSql('CREATE INDEX idx_af5a2a3ca76ed395 ON flower_cycle_completion (user_id)');
        $this->addSql('COMMENT ON COLUMN flower_cycle_completion.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN flower_cycle_completion.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN flower_cycle_completion.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT fk_86ffd285a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD CONSTRAINT fk_af5a2a3ca76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD CONSTRAINT fk_af5a2a3c2c09d409 FOREIGN KEY (flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD project_description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD is_annual_fee_pending BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE donation ADD donation_type VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE donation ADD cycle_position INT NOT NULL');
        $this->addSql('ALTER TABLE donation ADD stripe_payment_intent_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD coinpayments_transaction_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD crypto_currency VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD crypto_amount NUMERIC(18, 8) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD exchange_rate NUMERIC(10, 6) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD confirmations_needed SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD confirmations_received SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD status_url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD destination_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD crypto_status VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD solidarity_distribution_status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE donation ADD status VARCHAR(20) NOT NULL');
    }
}
