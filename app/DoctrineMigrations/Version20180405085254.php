<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180405085254 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE member_commission (member_commission_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, member_commission_member_id BIGINT UNSIGNED NOT NULL, member_commission_currency_id INT UNSIGNED NOT NULL, member_commission_product_id INT UNSIGNED NOT NULL, member_commission_resource_id VARCHAR(255) NOT NULL, member_commission_version INT UNSIGNED DEFAULT 1 NOT NULL, member_commission_commission NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, member_commission_status SMALLINT UNSIGNED DEFAULT 1 NOT NULL, member_commission_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL, member_commission_created_by BIGINT UNSIGNED NOT NULL, member_commission_created_at DATETIME NOT NULL, INDEX IDX_F72B1FB1F1F6407A (member_commission_currency_id), INDEX IDX_F72B1FB19B65EC29 (member_commission_product_id), INDEX status_index (member_commission_status), INDEX resource_index (member_commission_resource_id), INDEX version_index (member_commission_version), INDEX member_index (member_commission_member_id), INDEX isLatest_index (member_commission_is_latest), UNIQUE INDEX resource_version_unq (member_commission_resource_id, member_commission_version), PRIMARY KEY(member_commission_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE member_commission ADD CONSTRAINT FK_F72B1FB12F836513 FOREIGN KEY (member_commission_member_id) REFERENCES customer (customer_id)');
        $this->addSql('ALTER TABLE member_commission ADD CONSTRAINT FK_F72B1FB1F1F6407A FOREIGN KEY (member_commission_currency_id) REFERENCES currency (currency_id)');
        $this->addSql('ALTER TABLE member_commission ADD CONSTRAINT FK_F72B1FB19B65EC29 FOREIGN KEY (member_commission_product_id) REFERENCES product (product_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE member_commission');
    }
}
