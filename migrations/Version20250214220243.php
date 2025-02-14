<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214220243 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_method DROP CONSTRAINT fk_7b61a1f6a76ed395');
        $this->addSql('DROP INDEX idx_7b61a1f6a76ed395');
        $this->addSql('ALTER TABLE payment_method RENAME COLUMN user_id TO owner_id');
        $this->addSql('ALTER TABLE payment_method ADD CONSTRAINT FK_7B61A1F67E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_7B61A1F67E3C61F9 ON payment_method (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE payment_method DROP CONSTRAINT FK_7B61A1F67E3C61F9');
        $this->addSql('DROP INDEX IDX_7B61A1F67E3C61F9');
        $this->addSql('ALTER TABLE payment_method RENAME COLUMN owner_id TO user_id');
        $this->addSql('ALTER TABLE payment_method ADD CONSTRAINT fk_7b61a1f6a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_7b61a1f6a76ed395 ON payment_method (user_id)');
    }
}
