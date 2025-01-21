<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250121203705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE flower_cycle_completion (id SERIAL NOT NULL, user_id INT NOT NULL, flower_id INT NOT NULL, cycle_number INT NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, wallet_amount NUMERIC(10, 2) NOT NULL, solidarity_amount NUMERIC(10, 2) NOT NULL, cycle_positions JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AF5A2A3CA76ED395 ON flower_cycle_completion (user_id)');
        $this->addSql('CREATE INDEX IDX_AF5A2A3C2C09D409 ON flower_cycle_completion (flower_id)');
        $this->addSql('COMMENT ON COLUMN flower_cycle_completion.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN flower_cycle_completion.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN flower_cycle_completion.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE kyc_verification (id SERIAL NOT NULL, user_id INT NOT NULL, reference_id VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, document_paths JSON NOT NULL, submitted_data JSON NOT NULL, submitted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, processed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, admin_comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6D6B7EA76ED395 ON kyc_verification (user_id)');
        $this->addSql('CREATE TABLE payment_method (id SERIAL NOT NULL, user_id INT NOT NULL, method_type VARCHAR(20) NOT NULL, stripe_payment_method_id VARCHAR(255) DEFAULT NULL, crypto_currency VARCHAR(255) DEFAULT NULL, crypto_address VARCHAR(255) DEFAULT NULL, is_default BOOLEAN NOT NULL, last_four VARCHAR(255) DEFAULT NULL, card_brand VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7B61A1F6A76ED395 ON payment_method (user_id)');
        $this->addSql('COMMENT ON COLUMN payment_method.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN payment_method.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE system_configuration (key VARCHAR(255) NOT NULL, value TEXT NOT NULL, type VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(key))');
        $this->addSql('COMMENT ON COLUMN system_configuration.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN system_configuration.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD CONSTRAINT FK_AF5A2A3CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD CONSTRAINT FK_AF5A2A3C2C09D409 FOREIGN KEY (flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE kyc_verification ADD CONSTRAINT FK_6D6B7EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_method ADD CONSTRAINT FK_7B61A1F6A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE donation DROP payment_method');
        $this->addSql('ALTER TABLE membership DROP payment_method');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP CONSTRAINT FK_AF5A2A3CA76ED395');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP CONSTRAINT FK_AF5A2A3C2C09D409');
        $this->addSql('ALTER TABLE kyc_verification DROP CONSTRAINT FK_6D6B7EA76ED395');
        $this->addSql('ALTER TABLE payment_method DROP CONSTRAINT FK_7B61A1F6A76ED395');
        $this->addSql('DROP TABLE flower_cycle_completion');
        $this->addSql('DROP TABLE kyc_verification');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP TABLE system_configuration');
        $this->addSql('ALTER TABLE donation ADD payment_method VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE membership ADD payment_method VARCHAR(20) NOT NULL');
    }
}
