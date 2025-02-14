<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250214220817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('TRUNCATE TABLE withdrawal RESTART IDENTITY CASCADE');
        $this->addSql('ALTER TABLE withdrawal ADD withdrawal_method_id INT NOT NULL');
        $this->addSql('ALTER TABLE withdrawal DROP withdrawal_method');
        $this->addSql('ALTER TABLE withdrawal ADD CONSTRAINT FK_6D2D3B458F15033 FOREIGN KEY (withdrawal_method_id) REFERENCES payment_method (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6D2D3B458F15033 ON withdrawal (withdrawal_method_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE withdrawal DROP CONSTRAINT FK_6D2D3B458F15033');
        $this->addSql('DROP INDEX IDX_6D2D3B458F15033');
        $this->addSql('ALTER TABLE withdrawal ADD withdrawal_method VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE withdrawal DROP withdrawal_method_id');
    }
}
