<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241218213434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE main_slider ADD height INT DEFAULT NULL');
        $this->addSql('ALTER TABLE main_slider ADD dominant_color VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE main_slider ADD file_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE main_slider ADD mime_type VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE main_slider DROP image');
        $this->addSql('ALTER TABLE main_slider RENAME COLUMN image_size TO width');
        $this->addSql('ALTER TABLE main_slider RENAME COLUMN image_name TO file_name');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE main_slider ADD image VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE main_slider ADD image_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE main_slider ADD image_size INT DEFAULT NULL');
        $this->addSql('ALTER TABLE main_slider DROP width');
        $this->addSql('ALTER TABLE main_slider DROP height');
        $this->addSql('ALTER TABLE main_slider DROP dominant_color');
        $this->addSql('ALTER TABLE main_slider DROP file_name');
        $this->addSql('ALTER TABLE main_slider DROP file_size');
        $this->addSql('ALTER TABLE main_slider DROP mime_type');
    }
}
