<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181210091512 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `bitcoin_rate_setting` ADD `bitcoin_rate_setting_type` TINYINT(1) UNSIGNED DEFAULT 1 NOT NULL AFTER `bitcoin_rate_setting_is_default`');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `bitcoin_rate_setting` DROP COLUMN `bitcoin_rate_setting_type`');

    }
}
