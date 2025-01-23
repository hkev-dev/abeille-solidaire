<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250123211337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // First add columns with payment_status as nullable
        $this->addSql('ALTER TABLE donation ADD stripe_payment_intent_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD coinpayments_transaction_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD crypto_withdrawal_transaction_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD payment_provider VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE donation ADD payment_status VARCHAR(20) DEFAULT NULL');

        // Update existing records to have a payment status
        $this->addSql("UPDATE donation SET payment_status = 'completed'");

        // Now make payment_status non-nullable
        $this->addSql('ALTER TABLE donation ALTER COLUMN payment_status SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE donation DROP stripe_payment_intent_id');
        $this->addSql('ALTER TABLE donation DROP coinpayments_transaction_id');
        $this->addSql('ALTER TABLE donation DROP crypto_withdrawal_transaction_id');
        $this->addSql('ALTER TABLE donation DROP payment_provider');
        $this->addSql('ALTER TABLE donation DROP payment_status');
    }
}
