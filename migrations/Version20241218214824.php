<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241218214824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE main_slider ADD original_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE main_slider DROP width');
        $this->addSql('ALTER TABLE main_slider DROP height');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE main_slider ADD width INT DEFAULT NULL');
        $this->addSql('ALTER TABLE main_slider ADD height INT DEFAULT NULL');
        $this->addSql('ALTER TABLE main_slider DROP original_name');
    }
}
