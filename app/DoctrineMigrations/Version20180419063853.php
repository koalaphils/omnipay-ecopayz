<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180419063853 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE marketing_tool (marketing_tool_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, marketing_tool_member_id BIGINT UNSIGNED DEFAULT NULL, marketing_tool_affiliate_link VARCHAR(255) NOT NULL, marketing_tool_promo_code VARCHAR(255) DEFAULT NULL, marketing_tool_resource_id VARCHAR(255) NOT NULL, marketing_tool_version INT UNSIGNED DEFAULT 1 NOT NULL, marketing_tool_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL, marketing_tool_created_by BIGINT UNSIGNED NOT NULL, marketing_tool_created_at DATETIME NOT NULL, INDEX IDX_A2D4DC89824B58A4 (marketing_tool_member_id), PRIMARY KEY(marketing_tool_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE marketing_tool ADD CONSTRAINT FK_A2D4DC89824B58A4 FOREIGN KEY (marketing_tool_member_id) REFERENCES customer (customer_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE marketing_tool');
    }
}
