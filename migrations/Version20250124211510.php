<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250124211510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE project_backing_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE project_reward_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE project_story_id_seq CASCADE');
        $this->addSql('ALTER TABLE project_story DROP CONSTRAINT fk_f8030da6166d1f9c');
        $this->addSql('ALTER TABLE project_reward DROP CONSTRAINT fk_59759919166d1f9c');
        $this->addSql('ALTER TABLE project_backing DROP CONSTRAINT fk_89e86dca166d1f9c');
        $this->addSql('ALTER TABLE project_backing DROP CONSTRAINT fk_89e86dca59543840');
        $this->addSql('DROP TABLE project_story');
        $this->addSql('DROP TABLE project_reward');
        $this->addSql('DROP TABLE project_backing');
        $this->addSql('ALTER TABLE project ADD start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE project DROP backers');
        $this->addSql('ALTER TABLE project DROP location');
        $this->addSql('ALTER TABLE project ALTER goal TYPE NUMERIC(10, 2)');
        $this->addSql('ALTER TABLE project ALTER pledged TYPE NUMERIC(10, 2)');
        $this->addSql('ALTER TABLE project ALTER end_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN project.start_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project.end_date IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE project_backing_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE project_reward_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE project_story_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE project_story (id SERIAL NOT NULL, project_id INT NOT NULL, key_points JSON NOT NULL, paragraphs JSON NOT NULL, main_image VARCHAR(255) DEFAULT NULL, secondary_image VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_f8030da6166d1f9c ON project_story (project_id)');
        $this->addSql('COMMENT ON COLUMN project_story.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_story.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project_reward (id SERIAL NOT NULL, project_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, estimated_delivery TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, backer_count INT NOT NULL, backer_limit INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_59759919166d1f9c ON project_reward (project_id)');
        $this->addSql('COMMENT ON COLUMN project_reward.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_reward.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project_backing (id SERIAL NOT NULL, project_id INT NOT NULL, backer_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, comment TEXT DEFAULT NULL, is_anonymous BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_89e86dca59543840 ON project_backing (backer_id)');
        $this->addSql('CREATE INDEX idx_89e86dca166d1f9c ON project_backing (project_id)');
        $this->addSql('COMMENT ON COLUMN project_backing.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_backing.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE project_story ADD CONSTRAINT fk_f8030da6166d1f9c FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_reward ADD CONSTRAINT fk_59759919166d1f9c FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_backing ADD CONSTRAINT fk_89e86dca166d1f9c FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_backing ADD CONSTRAINT fk_89e86dca59543840 FOREIGN KEY (backer_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project ADD backers INT NOT NULL');
        $this->addSql('ALTER TABLE project ADD location VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE project DROP start_date');
        $this->addSql('ALTER TABLE project ALTER goal TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE project ALTER pledged TYPE DOUBLE PRECISION');
        $this->addSql('ALTER TABLE project ALTER end_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('COMMENT ON COLUMN project.end_date IS NULL');
    }
}
