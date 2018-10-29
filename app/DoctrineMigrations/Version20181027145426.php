<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181027145426 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE customer_payment_option_temp');
        $this->addSql('DROP TABLE customer_product_temp');
        $this->addSql('DROP TABLE customer_temp');
        $this->addSql('DROP TABLE dwl_temp');
        $this->addSql('DROP TABLE sub_transaction_temp');
        $this->addSql('DROP TABLE transaction_temp');
        $this->addSql('DROP TABLE user_temp');
        $this->addSql('ALTER TABLE dwl CHANGE dwl_updated_at dwl_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE customer ADD customer_pin_user_code VARCHAR(255) NOT NULL, ADD customer_pin_login_id VARCHAR(255) NOT NULL, CHANGE customer_currency_id customer_currency_id INT UNSIGNED DEFAULT NULL, CHANGE customer_tags customer_tags JSON DEFAULT \'\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE member_commission CHANGE member_commission_is_latest member_commission_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE country ADD country_phone_code VARCHAR(6) NOT NULL');
        $this->addSql('ALTER TABLE product_commission CHANGE product_commission_is_latest product_commission_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE marketing_tool CHANGE marketing_tool_promo_code marketing_tool_promo_code VARCHAR(255) DEFAULT \'\', CHANGE marketing_tool_is_latest marketing_tool_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE product RENAME INDEX uniq_d34a04add3cb5ca7 TO name_unq');
        $this->addSql('ALTER TABLE currency_rate CHANGE currency_rate_rate currency_rate_rate NUMERIC(65, 10) DEFAULT \'1\' NOT NULL, CHANGE currency_rate_is_latest currency_rate_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL, CHANGE currency_rate_destination_rate currency_rate_destination_rate NUMERIC(65, 10) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE payment_option CHANGE payment_option_sort payment_option_sort VARCHAR(5) DEFAULT \'\' NOT NULL');
        $this->addSql('ALTER TABLE risk_setting CHANGE risk_setting_is_latest risk_setting_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user ADD user_phone_number VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE sub_transaction CHANGE subtransaction_dwl_id subtransaction_dwl_id BIGINT AS (subtransaction_details->"$.dwl.id"), CHANGE subtransaction_dwl_turnover subtransaction_dwl_turnover NUMERIC(65, 10) AS (subtransaction_details->"$.dwl.turnover"), CHANGE subtransaction_dwl_winloss subtransaction_dwl_winloss NUMERIC(65, 10) AS (subtransaction_details->"$.dwl.winLoss")');
        $this->addSql('ALTER TABLE customer_product CHANGE cproduct_bet_sync_id cproduct_bet_sync_id BIGINT AS (cproduct_details->"$.brokerage.sync_id")');
        $this->addSql('ALTER TABLE customer_product RENAME INDEX betsyncid_index TO bet_sync_id_index');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CHANGE transaction_to_customer transaction_to_customer BIGINT AS (transaction_other_details->"$.toCustomer"), CHANGE dwl_id dwl_id BIGINT AS (transaction_other_details->"$.dwl.id"), CHANGE transaction_bet_id transaction_bet_id BIGINT AS (transaction_other_details->"$.bet_id"), CHANGE transaction_bet_event_id transaction_bet_event_id BIGINT AS (transaction_other_details->"$.event_id"), CHANGE transaction_commission_computed_original transaction_commission_computed_original NUMERIC(65, 10) AS (transaction_other_details->"$.commission.computed.original")');
        $this->addSql('ALTER TABLE currency ADD CONSTRAINT FK_6956883FF519F7DA FOREIGN KEY (currency_updated_by) REFERENCES user (user_id)');
        $this->addSql('CREATE INDEX IDX_6956883FF519F7DA ON currency (currency_updated_by)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE customer_payment_option_temp (old_id BIGINT UNSIGNED DEFAULT NULL, new_id BIGINT UNSIGNED DEFAULT NULL, INDEX old_id_idx (old_id), INDEX new_id_idx (new_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer_product_temp (old_id BIGINT UNSIGNED DEFAULT NULL, new_id BIGINT UNSIGNED DEFAULT NULL, INDEX old_id_idx (old_id), INDEX new_id_idx (new_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer_temp (old_id BIGINT UNSIGNED DEFAULT NULL, new_id BIGINT UNSIGNED DEFAULT NULL, INDEX old_id_idx (old_id), INDEX new_id_idx (new_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dwl_temp (old_id BIGINT UNSIGNED DEFAULT NULL, new_id BIGINT UNSIGNED DEFAULT NULL, INDEX old_id_idx (old_id), INDEX new_id_idx (new_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sub_transaction_temp (old_id BIGINT UNSIGNED DEFAULT NULL, new_id BIGINT UNSIGNED DEFAULT NULL, INDEX old_id_idx (old_id), INDEX new_id_idx (new_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction_temp (old_id BIGINT UNSIGNED DEFAULT NULL, new_id BIGINT UNSIGNED DEFAULT NULL, INDEX old_id_idx (old_id), INDEX new_id_idx (new_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_temp (old_id BIGINT UNSIGNED DEFAULT NULL, new_id BIGINT UNSIGNED DEFAULT NULL, INDEX old_id_idx (old_id), INDEX new_id_idx (new_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE country DROP country_phone_code');
        $this->addSql('ALTER TABLE currency DROP FOREIGN KEY FK_6956883FF519F7DA');
        $this->addSql('DROP INDEX IDX_6956883FF519F7DA ON currency');
        $this->addSql('ALTER TABLE currency_rate CHANGE currency_rate_rate currency_rate_rate NUMERIC(65, 10) DEFAULT \'0.0000000000\' NOT NULL, CHANGE currency_rate_destination_rate currency_rate_destination_rate NUMERIC(65, 10) DEFAULT \'0.0000000000\' NOT NULL, CHANGE currency_rate_is_latest currency_rate_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE customer DROP customer_pin_user_code, DROP customer_pin_login_id, CHANGE customer_currency_id customer_currency_id INT UNSIGNED NOT NULL, CHANGE customer_tags customer_tags JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE customer_product CHANGE cproduct_bet_sync_id cproduct_bet_sync_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_product RENAME INDEX bet_sync_id_index TO betSyncId_index');
        $this->addSql('ALTER TABLE dwl CHANGE dwl_updated_at dwl_updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE marketing_tool CHANGE marketing_tool_promo_code marketing_tool_promo_code VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE marketing_tool_is_latest marketing_tool_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE member_commission CHANGE member_commission_is_latest member_commission_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE payment_option CHANGE payment_option_sort payment_option_sort CHAR(5) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE product RENAME INDEX name_unq TO UNIQ_D34A04ADD3CB5CA7');
        $this->addSql('ALTER TABLE product_commission CHANGE product_commission_is_latest product_commission_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE risk_setting CHANGE risk_setting_is_latest risk_setting_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE sub_transaction CHANGE subtransaction_dwl_id subtransaction_dwl_id BIGINT DEFAULT NULL, CHANGE subtransaction_dwl_turnover subtransaction_dwl_turnover NUMERIC(65, 10) DEFAULT NULL, CHANGE subtransaction_dwl_winloss subtransaction_dwl_winloss NUMERIC(65, 10) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at DATETIME NOT NULL, CHANGE transaction_to_customer transaction_to_customer INT DEFAULT NULL, CHANGE dwl_id dwl_id INT DEFAULT NULL, CHANGE transaction_bet_id transaction_bet_id BIGINT DEFAULT NULL, CHANGE transaction_bet_event_id transaction_bet_event_id BIGINT DEFAULT NULL, CHANGE transaction_commission_computed_original transaction_commission_computed_original NUMERIC(65, 10) DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP user_phone_number');
    }
}
