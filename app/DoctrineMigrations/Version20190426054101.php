<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190426054101 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('UPDATE `setting` SET `setting_value` = JSON_SET(`setting_value`, "$.paymentOption", "BITCOIN") WHERE `setting_code` = "bitcoin.setting"');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
