<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250505130332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE causet_update_id_seq CASCADE');
        $this->addSql('ALTER TABLE causet_update DROP CONSTRAINT fk_5f25465b66e2221e');
        $this->addSql('DROP TABLE causet_update');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE causet_update_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE causet_update (id SERIAL NOT NULL, cause_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, image_name VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_5f25465b66e2221e ON causet_update (cause_id)');
        $this->addSql('COMMENT ON COLUMN causet_update.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN causet_update.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE causet_update ADD CONSTRAINT fk_5f25465b66e2221e FOREIGN KEY (cause_id) REFERENCES cause (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
