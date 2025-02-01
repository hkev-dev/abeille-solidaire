<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250201150024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE donation ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD matrix_position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD matrix_depth INT NOT NULL');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A0727ACA70 FOREIGN KEY (parent_id) REFERENCES donation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_31E581A0727ACA70 ON donation (parent_id)');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT fk_8d93d649f72f65a5');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT fk_8d93d649727aca70');
        $this->addSql('DROP INDEX idx_8d93d649727aca70');
        $this->addSql('DROP INDEX idx_8d93d649f72f65a5');
        $this->addSql('ALTER TABLE "user" DROP current_flower_id');
        $this->addSql('ALTER TABLE "user" DROP parent_id');
        $this->addSql('ALTER TABLE "user" DROP matrix_position');
        $this->addSql('ALTER TABLE "user" DROP matrix_depth');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ADD current_flower_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD parent_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD matrix_position INT DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD matrix_depth INT NOT NULL');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_8d93d649f72f65a5 FOREIGN KEY (current_flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT fk_8d93d649727aca70 FOREIGN KEY (parent_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_8d93d649727aca70 ON "user" (parent_id)');
        $this->addSql('CREATE INDEX idx_8d93d649f72f65a5 ON "user" (current_flower_id)');
        $this->addSql('ALTER TABLE donation DROP CONSTRAINT FK_31E581A0727ACA70');
        $this->addSql('DROP INDEX IDX_31E581A0727ACA70');
        $this->addSql('ALTER TABLE donation DROP parent_id');
        $this->addSql('ALTER TABLE donation DROP matrix_position');
        $this->addSql('ALTER TABLE donation DROP matrix_depth');
    }
}
