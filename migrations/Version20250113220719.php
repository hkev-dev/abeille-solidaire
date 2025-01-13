<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250113220719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE kyc_verification (id SERIAL NOT NULL, user_id INT NOT NULL, reference_id VARCHAR(255) NOT NULL, status VARCHAR(20) NOT NULL, document_paths JSON NOT NULL, submitted_data JSON NOT NULL, submitted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, processed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, admin_comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6D6B7EA76ED395 ON kyc_verification (user_id)');
        $this->addSql('ALTER TABLE kyc_verification ADD CONSTRAINT FK_6D6B7EA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" DROP kyc_provider');
        $this->addSql('ALTER TABLE "user" DROP kyc_reference_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE kyc_verification DROP CONSTRAINT FK_6D6B7EA76ED395');
        $this->addSql('DROP TABLE kyc_verification');
        $this->addSql('ALTER TABLE "user" ADD kyc_provider VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD kyc_reference_id VARCHAR(255) DEFAULT NULL');
    }
}
