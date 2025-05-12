<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250512180542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ponctual_donation (id SERIAL NOT NULL, cause_id INT DEFAULT NULL, amount DOUBLE PRECISION NOT NULL, donor_name VARCHAR(255) NOT NULL, is_anonymous BOOLEAN NOT NULL, _user_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2008BF8966E2221E ON ponctual_donation (cause_id)');
        $this->addSql('COMMENT ON COLUMN ponctual_donation.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN ponctual_donation.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE ponctual_donation ADD CONSTRAINT FK_2008BF8966E2221E FOREIGN KEY (cause_id) REFERENCES cause (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE ponctual_donation DROP CONSTRAINT FK_2008BF8966E2221E');
        $this->addSql('DROP TABLE ponctual_donation');
    }
}
