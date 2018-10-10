<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180406131857 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE product_commission (product_commission_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, product_commission_product_id INT UNSIGNED NOT NULL, product_commission_resource_id VARCHAR(255) NOT NULL, product_commission_version INT UNSIGNED DEFAULT 1 NOT NULL, product_commission_commission NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, product_commission_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL, product_commission_created_by BIGINT UNSIGNED NOT NULL, product_commission_created_at DATETIME NOT NULL, INDEX resource_index (product_commission_resource_id), INDEX version_index (product_commission_version), INDEX member_index (product_commission_product_id), INDEX isLatest_index (product_commission_is_latest), UNIQUE INDEX resource_version_unq (product_commission_resource_id, product_commission_version), UNIQUE INDEX product_version_unq (product_commission_product_id, product_commission_version), PRIMARY KEY(product_commission_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_commission ADD CONSTRAINT FK_8F025E73423FABDB FOREIGN KEY (product_commission_product_id) REFERENCES product (product_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE product_commission');
    }
}
