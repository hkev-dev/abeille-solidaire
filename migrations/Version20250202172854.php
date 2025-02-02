<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250202172854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE earning DROP CONSTRAINT fk_b00f11214dc1279c');
        $this->addSql('DROP INDEX idx_b00f11214dc1279c');
        $this->addSql('ALTER TABLE earning ADD donor_id INT NOT NULL');
        $this->addSql('ALTER TABLE earning ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE earning ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE earning RENAME COLUMN donation_id TO beneficiary_id');
        $this->addSql('COMMENT ON COLUMN earning.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN earning.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE earning ADD CONSTRAINT FK_B00F1121ECCAAFA0 FOREIGN KEY (beneficiary_id) REFERENCES donation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earning ADD CONSTRAINT FK_B00F11213DD7B7A7 FOREIGN KEY (donor_id) REFERENCES donation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_B00F1121ECCAAFA0 ON earning (beneficiary_id)');
        $this->addSql('CREATE INDEX IDX_B00F11213DD7B7A7 ON earning (donor_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE earning DROP CONSTRAINT FK_B00F1121ECCAAFA0');
        $this->addSql('ALTER TABLE earning DROP CONSTRAINT FK_B00F11213DD7B7A7');
        $this->addSql('DROP INDEX IDX_B00F1121ECCAAFA0');
        $this->addSql('DROP INDEX IDX_B00F11213DD7B7A7');
        $this->addSql('ALTER TABLE earning DROP donor_id');
        $this->addSql('ALTER TABLE earning DROP created_at');
        $this->addSql('ALTER TABLE earning DROP updated_at');
        $this->addSql('ALTER TABLE earning RENAME COLUMN beneficiary_id TO donation_id');
        $this->addSql('ALTER TABLE earning ADD CONSTRAINT fk_b00f11214dc1279c FOREIGN KEY (donation_id) REFERENCES donation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_b00f11214dc1279c ON earning (donation_id)');
    }
}
