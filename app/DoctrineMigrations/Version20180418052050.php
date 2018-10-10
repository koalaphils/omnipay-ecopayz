<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180418052050 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE risk_setting (risk_setting_id INT UNSIGNED AUTO_INCREMENT NOT NULL, risk_setting_risk_id VARCHAR(64) NOT NULL, risk_setting_is_active TINYINT(1) DEFAULT \'1\' NOT NULL, risk_setting_resource_id VARCHAR(255) NOT NULL, risk_setting_version INT UNSIGNED DEFAULT 1 NOT NULL, risk_setting_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL, risk_setting_created_at DATETIME NOT NULL, PRIMARY KEY(risk_setting_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_risk_setting (product_risk_setting_id INT UNSIGNED AUTO_INCREMENT NOT NULL, product_risk_setting_risk_id INT UNSIGNED DEFAULT NULL, product_risk_setting_product_id INT UNSIGNED DEFAULT NULL, product_risk_setting_percentage NUMERIC(65, 10) NOT NULL, INDEX IDX_58CFF4BC8FC8A2E5 (product_risk_setting_risk_id), INDEX IDX_58CFF4BC8B44726F (product_risk_setting_product_id), PRIMARY KEY(product_risk_setting_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_risk_setting ADD CONSTRAINT FK_58CFF4BC8FC8A2E5 FOREIGN KEY (product_risk_setting_risk_id) REFERENCES risk_setting (risk_setting_id)');
        $this->addSql('ALTER TABLE product_risk_setting ADD CONSTRAINT FK_58CFF4BC8B44726F FOREIGN KEY (product_risk_setting_product_id) REFERENCES product (product_id)');
        $this->addSql('ALTER TABLE customer ADD customer_risk_setting VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product_risk_setting DROP FOREIGN KEY FK_58CFF4BC8FC8A2E5');
        $this->addSql('DROP TABLE risk_setting');
        $this->addSql('DROP TABLE product_risk_setting');
        $this->addSql('ALTER TABLE customer DROP customer_risk_setting');
    }
}
