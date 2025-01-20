<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250120184228 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE queued_referral_placement_id_seq CASCADE');
        $this->addSql('ALTER TABLE queued_referral_placement DROP CONSTRAINT fk_704876163ccaa4b7');
        $this->addSql('ALTER TABLE queued_referral_placement DROP CONSTRAINT fk_704876162c09d409');
        $this->addSql('DROP TABLE queued_referral_placement');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT fk_8d93d649798c22db');
        $this->addSql('DROP INDEX idx_user_referral_code');
        $this->addSql('DROP INDEX idx_8d93d649798c22db');
        $this->addSql('DROP INDEX uniq_8d93d6496447454a');
        $this->addSql('ALTER TABLE "user" ADD matrix_position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD matrix_depth INT NOT NULL');
        $this->addSql('ALTER TABLE "user" DROP referral_code');
        $this->addSql('ALTER TABLE "user" ALTER is_annual_fee_pending DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN referrer_id TO parent_id');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649727ACA70 FOREIGN KEY (parent_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_8D93D649727ACA70 ON "user" (parent_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE queued_referral_placement_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE queued_referral_placement (id SERIAL NOT NULL, referral_id INT NOT NULL, flower_id INT NOT NULL, queued_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_704876162c09d409 ON queued_referral_placement (flower_id)');
        $this->addSql('CREATE INDEX idx_704876163ccaa4b7 ON queued_referral_placement (referral_id)');
        $this->addSql('COMMENT ON COLUMN queued_referral_placement.queued_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN queued_referral_placement.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN queued_referral_placement.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE queued_referral_placement ADD CONSTRAINT fk_704876163ccaa4b7 FOREIGN KEY (referral_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE queued_referral_placement ADD CONSTRAINT fk_704876162c09d409 FOREIGN KEY (flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649727ACA70');
        $this->addSql('DROP INDEX IDX_8D93D649727ACA70');
        $this->addSql('ALTER TABLE "user" ADD referrer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD referral_code VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE "user" DROP parent_id');
        $this->addSql('ALTER TABLE "user" DROP matrix_position');
        $this->addSql('ALTER TABLE "user" DROP matrix_depth');
        $this->addSql('ALTER TABLE "user" ALTER is_annual_fee_pending SET DEFAULT false');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_8d93d649798c22db FOREIGN KEY (referrer_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_user_referral_code ON "user" (referral_code)');
        $this->addSql('CREATE INDEX idx_8d93d649798c22db ON "user" (referrer_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_8d93d6496447454a ON "user" (referral_code)');
    }
}
