<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250121195449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes deprecated entities and migrates flower completions to JSON';
    }

    public function up(Schema $schema): void
    {
        // Add new JSON column to user table
        $this->addSql('ALTER TABLE "user" ADD flower_completions JSON DEFAULT NULL');
        
        // Migrate data from flower_cycle_completion to JSON
        $this->addSql('
            UPDATE "user" u SET flower_completions = (
                SELECT jsonb_object_agg(flower_id, completion_count)
                FROM (
                    SELECT user_id, flower_id, COUNT(*) as completion_count
                    FROM flower_cycle_completion
                    WHERE user_id = u.id
                    GROUP BY user_id, flower_id
                ) subquery
            )
        ');
        
        // Drop old tables and constraints
        $this->addSql('ALTER TABLE payment_method DROP CONSTRAINT IF EXISTS fk_7b61a1f6a76ed395');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP CONSTRAINT IF EXISTS fk_af5a2a3ca76ed395');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP CONSTRAINT IF EXISTS fk_af5a2a3c2c09d409');
        $this->addSql('ALTER TABLE kyc_verification DROP CONSTRAINT IF EXISTS fk_6d6b7ea76ed395');

        $this->addSql('DROP TABLE IF EXISTS payment_method CASCADE');
        $this->addSql('DROP TABLE IF EXISTS flower_cycle_completion CASCADE');
        $this->addSql('DROP TABLE IF EXISTS kyc_verification CASCADE');
        $this->addSql('DROP TABLE IF EXISTS system_configuration CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Create old tables
        $this->addSql('CREATE TABLE flower_cycle_completion (
            id SERIAL NOT NULL,
            user_id INT NOT NULL,
            flower_id INT NOT NULL,
            completed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        
        $this->addSql('ALTER TABLE flower_cycle_completion ADD CONSTRAINT fk_af5a2a3ca76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD CONSTRAINT fk_af5a2a3c2c09d409 FOREIGN KEY (flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        
        $this->addSql('ALTER TABLE "user" DROP COLUMN flower_completions');
    }
}
