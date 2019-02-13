<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180905034848 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE bitcoin_rate_setting (bitcoin_rate_setting_id INT UNSIGNED AUTO_INCREMENT NOT NULL, bitcoin_rate_setting_range_from NUMERIC(65, 10) DEFAULT \'0\', bitcoin_rate_setting_range_to NUMERIC(65, 10) DEFAULT \'0\', bitcoin_rate_setting_fixed_adjustment NUMERIC(65, 10) DEFAULT \'0\', bitcoin_rate_setting_percentage_adjustment NUMERIC(65, 10) DEFAULT \'0\', bitcoin_rate_setting_is_default TINYINT(1) DEFAULT \'0\' NOT NULL, bitcoin_rate_setting_created_by BIGINT UNSIGNED DEFAULT NULL, bitcoin_rate_setting_created_at DATETIME NOT NULL, bitcoin_rate_setting_updated_by BIGINT UNSIGNED DEFAULT NULL, bitcoin_rate_setting_updated_at DATETIME DEFAULT NULL, PRIMARY KEY(bitcoin_rate_setting_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('DROP TABLE bitcoin_rate_setting');
    }
}
