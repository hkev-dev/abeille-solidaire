<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250112220011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_method ADD stripe_payment_method_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_method ADD crypto_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_method DROP stripe_customer_id');
        $this->addSql('ALTER TABLE payment_method DROP coinpayments_tag');
        $this->addSql('ALTER TABLE "user" ADD stripe_customer_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD default_payment_method_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" DROP stripe_customer_id');
        $this->addSql('ALTER TABLE "user" DROP default_payment_method_id');
        $this->addSql('ALTER TABLE payment_method ADD stripe_customer_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_method ADD coinpayments_tag VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_method DROP stripe_payment_method_id');
        $this->addSql('ALTER TABLE payment_method DROP crypto_address');
    }
}
