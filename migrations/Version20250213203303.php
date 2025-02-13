<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250213203303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE kyc_verification DROP CONSTRAINT fk_6d6b7ea76ed395');
        $this->addSql('DROP INDEX idx_6d6b7ea76ed395');
        $this->addSql('ALTER TABLE kyc_verification RENAME COLUMN user_id TO author_id');
        $this->addSql('ALTER TABLE kyc_verification ADD CONSTRAINT FK_6D6B7EF675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6D6B7EF675F31B ON kyc_verification (author_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE kyc_verification DROP CONSTRAINT FK_6D6B7EF675F31B');
        $this->addSql('DROP INDEX IDX_6D6B7EF675F31B');
        $this->addSql('ALTER TABLE kyc_verification RENAME COLUMN author_id TO user_id');
        $this->addSql('ALTER TABLE kyc_verification ADD CONSTRAINT fk_6d6b7ea76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_6d6b7ea76ed395 ON kyc_verification (user_id)');
    }
}
