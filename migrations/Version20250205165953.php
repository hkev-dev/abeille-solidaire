<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250205165953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE donation ADD payment_reference VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE membership DROP CONSTRAINT fk_86ffd2854c3a3bb');
        $this->addSql('DROP INDEX uniq_86ffd2854c3a3bb');
        $this->addSql('ALTER TABLE membership ADD payment_provider VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE membership ADD payment_status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE membership ADD payment_reference VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE membership DROP payment_id');
        $this->addSql('UPDATE donation SET payment_reference = COALESCE(stripe_payment_intent_id, coinpayments_transaction_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE membership ADD payment_id INT NOT NULL');
        $this->addSql('ALTER TABLE membership DROP payment_provider');
        $this->addSql('ALTER TABLE membership DROP payment_status');
        $this->addSql('ALTER TABLE membership DROP payment_reference');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT fk_86ffd2854c3a3bb FOREIGN KEY (payment_id) REFERENCES donation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_86ffd2854c3a3bb ON membership (payment_id)');
        $this->addSql('ALTER TABLE donation DROP payment_reference');
    }
}
