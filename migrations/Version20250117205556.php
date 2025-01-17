<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250117205556 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_af5a2a3ca76ed3952c09d409');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD completed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD total_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD wallet_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD solidarity_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD cycle_positions JSON NOT NULL');
        $this->addSql('ALTER TABLE flower_cycle_completion RENAME COLUMN completion_count TO cycle_number');
        $this->addSql('COMMENT ON COLUMN flower_cycle_completion.completed_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP completed_at');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP total_amount');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP wallet_amount');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP solidarity_amount');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP cycle_positions');
        $this->addSql('ALTER TABLE flower_cycle_completion RENAME COLUMN cycle_number TO completion_count');
        $this->addSql('CREATE UNIQUE INDEX uniq_af5a2a3ca76ed3952c09d409 ON flower_cycle_completion (user_id, flower_id)');
    }
}
