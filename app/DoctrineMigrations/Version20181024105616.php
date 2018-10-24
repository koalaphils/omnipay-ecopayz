<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181024105616 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE currency_rate CHANGE currency_rate_is_latest currency_rate_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE customer CHANGE customer_tags customer_tags JSON DEFAULT \'\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE customer_product CHANGE cproduct_bet_sync_id cproduct_bet_sync_id BIGINT AS (cproduct_details->"$.brokerage.sync_id")');
        $this->addSql('ALTER TABLE dwl CHANGE dwl_updated_at dwl_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE marketing_tool CHANGE marketing_tool_is_latest marketing_tool_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE member_commission CHANGE member_commission_is_latest member_commission_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE product_commission CHANGE product_commission_is_latest product_commission_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE risk_setting CHANGE risk_setting_is_latest risk_setting_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE sub_transaction CHANGE subtransaction_dwl_id subtransaction_dwl_id BIGINT AS (subtransaction_details->"$.dwl.id"), CHANGE subtransaction_dwl_turnover subtransaction_dwl_turnover NUMERIC(65, 10) AS (subtransaction_details->"$.dwl.turnover"), CHANGE subtransaction_dwl_winloss subtransaction_dwl_winloss NUMERIC(65, 10) AS (subtransaction_details->"$.dwl.winLoss")');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CHANGE transaction_to_customer transaction_to_customer BIGINT AS (transaction_other_details->"$.toCustomer"), CHANGE dwl_id dwl_id BIGINT AS (transaction_other_details->"$.dwl.id"), CHANGE transaction_bet_id transaction_bet_id BIGINT AS (transaction_other_details->"$.bet_id"), CHANGE transaction_bet_event_id transaction_bet_event_id BIGINT AS (transaction_other_details->"$.event_id"), CHANGE transaction_commission_computed_original transaction_commission_computed_original NUMERIC(65, 10) AS (transaction_other_details->"$.commission.computed.original")');
        $this->addSql('DROP INDEX phone_number_index ON user');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE currency_rate CHANGE currency_rate_is_latest currency_rate_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE customer CHANGE customer_tags customer_tags JSON DEFAULT \'null\' NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE customer_product CHANGE cproduct_bet_sync_id cproduct_bet_sync_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE dwl CHANGE dwl_updated_at dwl_updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE marketing_tool CHANGE marketing_tool_is_latest marketing_tool_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE member_commission CHANGE member_commission_is_latest member_commission_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE product_commission CHANGE product_commission_is_latest product_commission_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE risk_setting CHANGE risk_setting_is_latest risk_setting_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE sub_transaction CHANGE subtransaction_dwl_id subtransaction_dwl_id BIGINT DEFAULT NULL, CHANGE subtransaction_dwl_turnover subtransaction_dwl_turnover NUMERIC(65, 10) DEFAULT NULL, CHANGE subtransaction_dwl_winloss subtransaction_dwl_winloss NUMERIC(65, 10) DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at DATETIME NOT NULL, CHANGE transaction_to_customer transaction_to_customer BIGINT DEFAULT NULL, CHANGE dwl_id dwl_id BIGINT DEFAULT NULL, CHANGE transaction_bet_id transaction_bet_id BIGINT DEFAULT NULL, CHANGE transaction_bet_event_id transaction_bet_event_id BIGINT DEFAULT NULL, CHANGE transaction_commission_computed_original transaction_commission_computed_original NUMERIC(65, 10) DEFAULT NULL');
        $this->addSql('CREATE INDEX phone_number_index ON user (user_phone_number)');
    }
}
