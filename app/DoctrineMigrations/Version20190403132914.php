<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190403132914 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('UPDATE `setting` SET `setting_value` = \'{"product\": "PINBET", "transaction": {"deposit": {"status": 2}, "withdraw": {"status": 4}}}\' WHERE `setting_id` = 114');
        $this->addSql('UPDATE `sub_transaction` SET `subtransaction_details` = JSON_SET(`subtransaction_details`, \'$.pinnacle\', JSON_OBJECT("transacted", true)) WHERE `subtransaction_transaction_id` IN (SELECT `transaction_id` FROM `transaction` WHERE transaction_status = 2)');
    }

    public function down(Schema $schema) : void
    {

    }
}
