<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214214006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_method ADD rib_iban VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_method ADD rib_bic VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_method ADD rib_owner VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE payment_method DROP rib_iban');
        $this->addSql('ALTER TABLE payment_method DROP rib_bic');
        $this->addSql('ALTER TABLE payment_method DROP rib_owner');
    }
}
