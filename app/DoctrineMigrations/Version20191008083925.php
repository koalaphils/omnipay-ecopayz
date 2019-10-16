<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191008083925 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer ADD customer_allow_revenue_share TINYINT(1) DEFAULT \'0\' NOT NULL');

        $this->addSql('CREATE TABLE member_revenue_share (member_revenue_share_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, member_revenue_share_member_id BIGINT UNSIGNED NOT NULL, member_revenue_share_product_id INT UNSIGNED NOT NULL, member_revenue_share_resource_id VARCHAR(255) NOT NULL, member_revenue_share_settings JSON NOT NULL COMMENT \'(DC2Type:json)\', member_revenue_share_version INT UNSIGNED DEFAULT 1 NOT NULL, member_revenue_share_status SMALLINT UNSIGNED DEFAULT 1 NOT NULL, member_revenue_share_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL, member_revenue_share_created_by BIGINT UNSIGNED NOT NULL, member_revenue_share_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', INDEX status_index (member_revenue_share_status), INDEX resource_index (member_revenue_share_resource_id), INDEX product_index (member_revenue_share_product_id), INDEX version_index (member_revenue_share_version), INDEX member_index (member_revenue_share_member_id), INDEX isLatest_index (member_revenue_share_is_latest), UNIQUE INDEX resource_version_unq (member_revenue_share_resource_id, member_revenue_share_version), PRIMARY KEY(member_revenue_share_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE member_revenue_share ADD CONSTRAINT FK_DA010DB8977C02EC FOREIGN KEY (member_revenue_share_member_id) REFERENCES customer (customer_id)');
        $this->addSql('ALTER TABLE member_revenue_share ADD CONSTRAINT FK_DA010DB8B6DFFCC3 FOREIGN KEY (member_revenue_share_product_id) REFERENCES product (product_id)');

        $this->addSql('ALTER TABLE commission_period ADD commission_period_revenue_share_status TINYINT(1) DEFAULT \'1\' NOT NULL');

        $this->addSql('INSERT INTO `product` (`product_id`, `product_code`, `product_name`, `product_is_active`, `product_deleted_at`, `product_created_by`, `product_created_at`, `product_updated_by`, `product_updated_at`, `product_logo_uri`, `product_url`, `product_details`) VALUES
(2, \'PW\',   \'PIWI WALLET\',  1,  NULL,   1,  NOW(),  NULL,   NULL,   NULL,   NULL, \'{"piwi_wallet": true}\')');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP customer_allow_revenue_share');
        $this->addSql('DROP TABLE member_revenue_share');
        $this->addSql('ALTER TABLE commission_period DROP commission_period_revenue_share_status');
        $this->addSql('DELETE FROM product where product_id = 2');
    }
}
