<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250202170314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE earning (id SERIAL NOT NULL, donation_id INT DEFAULT NULL, flower_id INT DEFAULT NULL, amount DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B00F11214DC1279C ON earning (donation_id)');
        $this->addSql('CREATE INDEX IDX_B00F11212C09D409 ON earning (flower_id)');
        $this->addSql('ALTER TABLE earning ADD CONSTRAINT FK_B00F11214DC1279C FOREIGN KEY (donation_id) REFERENCES donation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE earning ADD CONSTRAINT FK_B00F11212C09D409 FOREIGN KEY (flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE donation DROP earnings');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE earning DROP CONSTRAINT FK_B00F11214DC1279C');
        $this->addSql('ALTER TABLE earning DROP CONSTRAINT FK_B00F11212C09D409');
        $this->addSql('DROP TABLE earning');
        $this->addSql('ALTER TABLE donation ADD earnings DOUBLE PRECISION DEFAULT NULL');
    }
}
