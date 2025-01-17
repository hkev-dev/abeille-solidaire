<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250117210428 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE queued_referral_placement (id SERIAL NOT NULL, referral_id INT NOT NULL, flower_id INT NOT NULL, queued_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_704876163CCAA4B7 ON queued_referral_placement (referral_id)');
        $this->addSql('CREATE INDEX IDX_704876162C09D409 ON queued_referral_placement (flower_id)');
        $this->addSql('COMMENT ON COLUMN queued_referral_placement.queued_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN queued_referral_placement.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN queued_referral_placement.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE queued_referral_placement ADD CONSTRAINT FK_704876163CCAA4B7 FOREIGN KEY (referral_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE queued_referral_placement ADD CONSTRAINT FK_704876162C09D409 FOREIGN KEY (flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE queued_referral_placement DROP CONSTRAINT FK_704876163CCAA4B7');
        $this->addSql('ALTER TABLE queued_referral_placement DROP CONSTRAINT FK_704876162C09D409');
        $this->addSql('DROP TABLE queued_referral_placement');
    }
}
