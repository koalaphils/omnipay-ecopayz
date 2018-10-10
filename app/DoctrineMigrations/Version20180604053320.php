<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180604053320 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE currency_rate (currency_rate_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, currency_rate_source_currency_id INT UNSIGNED NOT NULL, currency_rate_destination_currency_id INT UNSIGNED NOT NULL, currency_rate_resource_id VARCHAR(255) NOT NULL, currency_rate_version INT UNSIGNED DEFAULT 1 NOT NULL, currency_rate_rate NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, currency_rate_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL, currency_rate_created_by BIGINT UNSIGNED NOT NULL, currency_rate_created_at DATETIME NOT NULL, INDEX IDX_555B7C4D46B2FAAC (currency_rate_source_currency_id), INDEX IDX_555B7C4DBD3B7CB4 (currency_rate_destination_currency_id), INDEX version_index (currency_rate_version), INDEX isLatest_index (currency_rate_is_latest), UNIQUE INDEX resource_version_unq (currency_rate_resource_id, currency_rate_version), UNIQUE INDEX currency_version_unq (currency_rate_source_currency_id, currency_rate_version), PRIMARY KEY(currency_rate_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE currency_rate ADD CONSTRAINT FK_555B7C4D46B2FAAC FOREIGN KEY (currency_rate_source_currency_id) REFERENCES currency (currency_id)');
        $this->addSql('ALTER TABLE currency_rate ADD CONSTRAINT FK_555B7C4DBD3B7CB4 FOREIGN KEY (currency_rate_destination_currency_id) REFERENCES currency (currency_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE currency_rate');
    }
}
