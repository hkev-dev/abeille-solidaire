<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250103134640 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE comment (id SERIAL NOT NULL, article_id INT NOT NULL, author VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, content TEXT NOT NULL, email VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9474526C7294869C ON comment (article_id)');
        $this->addSql('COMMENT ON COLUMN comment.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN comment.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE donation (id SERIAL NOT NULL, donor_id INT NOT NULL, recipient_id INT NOT NULL, flower_id INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, donation_type VARCHAR(20) NOT NULL, cycle_position INT NOT NULL, transaction_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, coinpayments_transaction_id VARCHAR(255) DEFAULT NULL, crypto_currency VARCHAR(64) DEFAULT NULL, crypto_amount NUMERIC(18, 8) DEFAULT NULL, exchange_rate NUMERIC(10, 6) DEFAULT NULL, confirmations_needed SMALLINT DEFAULT NULL, confirmations_received SMALLINT DEFAULT NULL, status_url VARCHAR(255) DEFAULT NULL, destination_address VARCHAR(255) DEFAULT NULL, crypto_status VARCHAR(32) DEFAULT NULL, solidarity_distribution_status VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_31E581A03DD7B7A7 ON donation (donor_id)');
        $this->addSql('CREATE INDEX IDX_31E581A0E92F8F78 ON donation (recipient_id)');
        $this->addSql('CREATE INDEX IDX_31E581A02C09D409 ON donation (flower_id)');
        $this->addSql('COMMENT ON COLUMN donation.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN donation.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE event (id SERIAL NOT NULL, category_id INT NOT NULL, title VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BAE0AA7989D9B62 ON event (slug)');
        $this->addSql('CREATE INDEX IDX_3BAE0AA712469DE2 ON event (category_id)');
        $this->addSql('COMMENT ON COLUMN event.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN event.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE event_category (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, icon VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE event_content (id SERIAL NOT NULL, event_id INT DEFAULT NULL, description TEXT NOT NULL, requirements TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7B92990671F7E88B ON event_content (event_id)');
        $this->addSql('CREATE TABLE event_details (id SERIAL NOT NULL, event_id INT DEFAULT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE DEFAULT NULL, phone VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F771A22571F7E88B ON event_details (event_id)');
        $this->addSql('CREATE TABLE faq (id SERIAL NOT NULL, question VARCHAR(255) NOT NULL, answer TEXT NOT NULL, is_active BOOLEAN NOT NULL, position INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN faq.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN faq.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE flower (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, donation_amount NUMERIC(10, 2) NOT NULL, level INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN flower.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN flower.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE flower_cycle_completion (id SERIAL NOT NULL, user_id INT NOT NULL, flower_id INT NOT NULL, completion_count INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AF5A2A3CA76ED395 ON flower_cycle_completion (user_id)');
        $this->addSql('CREATE INDEX IDX_AF5A2A3C2C09D409 ON flower_cycle_completion (flower_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AF5A2A3CA76ED3952C09D409 ON flower_cycle_completion (user_id, flower_id)');
        $this->addSql('COMMENT ON COLUMN flower_cycle_completion.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN flower_cycle_completion.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE main_slider (id SERIAL NOT NULL, image_name VARCHAR(255) DEFAULT NULL, image_size INT DEFAULT NULL, subtitle VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, position INT NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN main_slider.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN main_slider.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE membership (id SERIAL NOT NULL, user_id INT NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, coinpayments_txn_id VARCHAR(255) DEFAULT NULL, crypto_currency VARCHAR(20) DEFAULT NULL, crypto_amount NUMERIC(18, 8) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_86FFD285A76ED395 ON membership (user_id)');
        $this->addSql('COMMENT ON COLUMN membership.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN membership.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE news_article (id SERIAL NOT NULL, category_id INT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, image VARCHAR(255) DEFAULT NULL, excerpt TEXT DEFAULT NULL, content TEXT NOT NULL, author VARCHAR(255) NOT NULL, comments_count INT NOT NULL, tags JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_55DE1280989D9B62 ON news_article (slug)');
        $this->addSql('CREATE INDEX IDX_55DE128012469DE2 ON news_article (category_id)');
        $this->addSql('COMMENT ON COLUMN news_article.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN news_article.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE news_category (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4F72BA90989D9B62 ON news_category (slug)');
        $this->addSql('CREATE TABLE payment_method (id SERIAL NOT NULL, user_id INT NOT NULL, method_type VARCHAR(20) NOT NULL, stripe_customer_id VARCHAR(255) DEFAULT NULL, coinpayments_tag VARCHAR(255) DEFAULT NULL, crypto_currency VARCHAR(255) DEFAULT NULL, is_default BOOLEAN NOT NULL, last_four VARCHAR(255) DEFAULT NULL, card_brand VARCHAR(50) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7B61A1F6A76ED395 ON payment_method (user_id)');
        $this->addSql('COMMENT ON COLUMN payment_method.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN payment_method.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project (id SERIAL NOT NULL, category_id INT NOT NULL, creator_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, goal DOUBLE PRECISION NOT NULL, pledged DOUBLE PRECISION NOT NULL, backers INT NOT NULL, image VARCHAR(255) DEFAULT NULL, location VARCHAR(255) NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, slug VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2FB3D0EE989D9B62 ON project (slug)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE12469DE2 ON project (category_id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE61220EA6 ON project (creator_id)');
        $this->addSql('COMMENT ON COLUMN project.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project_backing (id SERIAL NOT NULL, project_id INT NOT NULL, backer_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, comment TEXT DEFAULT NULL, is_anonymous BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_89E86DCA166D1F9C ON project_backing (project_id)');
        $this->addSql('CREATE INDEX IDX_89E86DCA59543840 ON project_backing (backer_id)');
        $this->addSql('COMMENT ON COLUMN project_backing.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_backing.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project_category (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, icon VARCHAR(255) NOT NULL, project_count INT NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN project_category.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_category.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project_faq (id SERIAL NOT NULL, project_id INT NOT NULL, question VARCHAR(255) NOT NULL, answer TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7A84AF2F166D1F9C ON project_faq (project_id)');
        $this->addSql('COMMENT ON COLUMN project_faq.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_faq.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project_review (id SERIAL NOT NULL, project_id INT NOT NULL, author_id INT NOT NULL, comment VARCHAR(1000) NOT NULL, rating INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6EE76A8C166D1F9C ON project_review (project_id)');
        $this->addSql('CREATE INDEX IDX_6EE76A8CF675F31B ON project_review (author_id)');
        $this->addSql('COMMENT ON COLUMN project_review.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_review.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project_reward (id SERIAL NOT NULL, project_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, estimated_delivery TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, backer_count INT NOT NULL, backer_limit INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_59759919166D1F9C ON project_reward (project_id)');
        $this->addSql('COMMENT ON COLUMN project_reward.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_reward.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project_story (id SERIAL NOT NULL, project_id INT NOT NULL, key_points JSON NOT NULL, paragraphs JSON NOT NULL, main_image VARCHAR(255) DEFAULT NULL, secondary_image VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F8030DA6166D1F9C ON project_story (project_id)');
        $this->addSql('COMMENT ON COLUMN project_story.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_story.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE project_update (id SERIAL NOT NULL, project_id INT NOT NULL, title VARCHAR(255) NOT NULL, content TEXT NOT NULL, is_milestone BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8F81DE32166D1F9C ON project_update (project_id)');
        $this->addSql('COMMENT ON COLUMN project_update.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN project_update.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE system_configuration (key VARCHAR(255) NOT NULL, value TEXT NOT NULL, type VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(key))');
        $this->addSql('COMMENT ON COLUMN system_configuration.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN system_configuration.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, current_flower_id INT DEFAULT NULL, referrer_id INT DEFAULT NULL, email VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified BOOLEAN NOT NULL, avatar VARCHAR(255) DEFAULT NULL, wallet_balance NUMERIC(10, 2) NOT NULL, project_description TEXT DEFAULT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, referral_code VARCHAR(32) NOT NULL, registration_payment_status VARCHAR(20) NOT NULL, waiting_since TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_kyc_verified BOOLEAN NOT NULL, kyc_verified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, kyc_provider VARCHAR(255) DEFAULT NULL, kyc_reference_id VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6496447454A ON "user" (referral_code)');
        $this->addSql('CREATE INDEX IDX_8D93D649F72F65A5 ON "user" (current_flower_id)');
        $this->addSql('CREATE INDEX IDX_8D93D649798C22DB ON "user" (referrer_id)');
        $this->addSql('COMMENT ON COLUMN "user".created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE withdrawal (id SERIAL NOT NULL, user_id INT NOT NULL, amount NUMERIC(10, 2) NOT NULL, withdrawal_method VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, processed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, failure_reason TEXT DEFAULT NULL, fee_amount NUMERIC(10, 2) NOT NULL, coinpayments_withdrawal_id VARCHAR(255) DEFAULT NULL, crypto_address VARCHAR(255) DEFAULT NULL, crypto_currency VARCHAR(20) DEFAULT NULL, crypto_amount NUMERIC(18, 8) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6D2D3B45A76ED395 ON withdrawal (user_id)');
        $this->addSql('COMMENT ON COLUMN withdrawal.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN withdrawal.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C7294869C FOREIGN KEY (article_id) REFERENCES news_article (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A03DD7B7A7 FOREIGN KEY (donor_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A0E92F8F78 FOREIGN KEY (recipient_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE donation ADD CONSTRAINT FK_31E581A02C09D409 FOREIGN KEY (flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA712469DE2 FOREIGN KEY (category_id) REFERENCES event_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_content ADD CONSTRAINT FK_7B92990671F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE event_details ADD CONSTRAINT FK_F771A22571F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD CONSTRAINT FK_AF5A2A3CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE flower_cycle_completion ADD CONSTRAINT FK_AF5A2A3C2C09D409 FOREIGN KEY (flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE news_article ADD CONSTRAINT FK_55DE128012469DE2 FOREIGN KEY (category_id) REFERENCES news_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment_method ADD CONSTRAINT FK_7B61A1F6A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE12469DE2 FOREIGN KEY (category_id) REFERENCES project_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE61220EA6 FOREIGN KEY (creator_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_backing ADD CONSTRAINT FK_89E86DCA166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_backing ADD CONSTRAINT FK_89E86DCA59543840 FOREIGN KEY (backer_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_faq ADD CONSTRAINT FK_7A84AF2F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_review ADD CONSTRAINT FK_6EE76A8C166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_review ADD CONSTRAINT FK_6EE76A8CF675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_reward ADD CONSTRAINT FK_59759919166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_story ADD CONSTRAINT FK_F8030DA6166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project_update ADD CONSTRAINT FK_8F81DE32166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649F72F65A5 FOREIGN KEY (current_flower_id) REFERENCES flower (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649798C22DB FOREIGN KEY (referrer_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE withdrawal ADD CONSTRAINT FK_6D2D3B45A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE comment DROP CONSTRAINT FK_9474526C7294869C');
        $this->addSql('ALTER TABLE donation DROP CONSTRAINT FK_31E581A03DD7B7A7');
        $this->addSql('ALTER TABLE donation DROP CONSTRAINT FK_31E581A0E92F8F78');
        $this->addSql('ALTER TABLE donation DROP CONSTRAINT FK_31E581A02C09D409');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA712469DE2');
        $this->addSql('ALTER TABLE event_content DROP CONSTRAINT FK_7B92990671F7E88B');
        $this->addSql('ALTER TABLE event_details DROP CONSTRAINT FK_F771A22571F7E88B');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP CONSTRAINT FK_AF5A2A3CA76ED395');
        $this->addSql('ALTER TABLE flower_cycle_completion DROP CONSTRAINT FK_AF5A2A3C2C09D409');
        $this->addSql('ALTER TABLE membership DROP CONSTRAINT FK_86FFD285A76ED395');
        $this->addSql('ALTER TABLE news_article DROP CONSTRAINT FK_55DE128012469DE2');
        $this->addSql('ALTER TABLE payment_method DROP CONSTRAINT FK_7B61A1F6A76ED395');
        $this->addSql('ALTER TABLE project DROP CONSTRAINT FK_2FB3D0EE12469DE2');
        $this->addSql('ALTER TABLE project DROP CONSTRAINT FK_2FB3D0EE61220EA6');
        $this->addSql('ALTER TABLE project_backing DROP CONSTRAINT FK_89E86DCA166D1F9C');
        $this->addSql('ALTER TABLE project_backing DROP CONSTRAINT FK_89E86DCA59543840');
        $this->addSql('ALTER TABLE project_faq DROP CONSTRAINT FK_7A84AF2F166D1F9C');
        $this->addSql('ALTER TABLE project_review DROP CONSTRAINT FK_6EE76A8C166D1F9C');
        $this->addSql('ALTER TABLE project_review DROP CONSTRAINT FK_6EE76A8CF675F31B');
        $this->addSql('ALTER TABLE project_reward DROP CONSTRAINT FK_59759919166D1F9C');
        $this->addSql('ALTER TABLE project_story DROP CONSTRAINT FK_F8030DA6166D1F9C');
        $this->addSql('ALTER TABLE project_update DROP CONSTRAINT FK_8F81DE32166D1F9C');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649F72F65A5');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649798C22DB');
        $this->addSql('ALTER TABLE withdrawal DROP CONSTRAINT FK_6D2D3B45A76ED395');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE donation');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_category');
        $this->addSql('DROP TABLE event_content');
        $this->addSql('DROP TABLE event_details');
        $this->addSql('DROP TABLE faq');
        $this->addSql('DROP TABLE flower');
        $this->addSql('DROP TABLE flower_cycle_completion');
        $this->addSql('DROP TABLE main_slider');
        $this->addSql('DROP TABLE membership');
        $this->addSql('DROP TABLE news_article');
        $this->addSql('DROP TABLE news_category');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_backing');
        $this->addSql('DROP TABLE project_category');
        $this->addSql('DROP TABLE project_faq');
        $this->addSql('DROP TABLE project_review');
        $this->addSql('DROP TABLE project_reward');
        $this->addSql('DROP TABLE project_story');
        $this->addSql('DROP TABLE project_update');
        $this->addSql('DROP TABLE system_configuration');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE withdrawal');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
