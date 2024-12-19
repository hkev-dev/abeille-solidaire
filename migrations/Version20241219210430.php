<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241219210430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert start_time and end_time from VARCHAR to TIME type';
    }

    public function up(Schema $schema): void
    {
        // Create temporary columns with new type
        $this->addSql('ALTER TABLE event_details ADD start_time_new TIME(0) WITHOUT TIME ZONE NULL');
        $this->addSql('ALTER TABLE event_details ADD end_time_new TIME(0) WITHOUT TIME ZONE NULL');

        // Convert existing data
        $this->addSql("UPDATE event_details SET start_time_new = start_time::time without time zone");
        $this->addSql("UPDATE event_details SET end_time_new = end_time::time without time zone");

        // Drop old columns
        $this->addSql('ALTER TABLE event_details DROP COLUMN start_time');
        $this->addSql('ALTER TABLE event_details DROP COLUMN end_time');

        // Rename new columns
        $this->addSql('ALTER TABLE event_details RENAME COLUMN start_time_new TO start_time');
        $this->addSql('ALTER TABLE event_details RENAME COLUMN end_time_new TO end_time');

        // Set NOT NULL constraint on start_time
        $this->addSql('ALTER TABLE event_details ALTER COLUMN start_time SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Create temporary varchar columns
        $this->addSql('ALTER TABLE event_details ADD start_time_old VARCHAR(255)');
        $this->addSql('ALTER TABLE event_details ADD end_time_old VARCHAR(255)');

        // Convert back to string
        $this->addSql("UPDATE event_details SET start_time_old = start_time::varchar");
        $this->addSql("UPDATE event_details SET end_time_old = end_time::varchar");

        // Drop time columns
        $this->addSql('ALTER TABLE event_details DROP COLUMN start_time');
        $this->addSql('ALTER TABLE event_details DROP COLUMN end_time');

        // Rename varchar columns
        $this->addSql('ALTER TABLE event_details RENAME COLUMN start_time_old TO start_time');
        $this->addSql('ALTER TABLE event_details RENAME COLUMN end_time_old TO end_time');

        // Set constraints
        $this->addSql('ALTER TABLE event_details ALTER COLUMN start_time SET NOT NULL');
        
        // Remove schema creation (it was auto-generated and not needed)
        // $this->addSql('CREATE SCHEMA public');
    }
}
