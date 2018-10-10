<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use DbBundle\Entity\Transaction;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180116101505 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            "UPDATE setting SET setting_value = JSON_SET(setting_value, '$.pending.filters', JSON_OBJECT('voided', ?, 'excludeStatus', JSON_ARRAY(?, ?))) WHERE setting_code = 'transaction.list.filters';",
            [false, Transaction::TRANSACTION_STATUS_END, Transaction::TRANSACTION_STATUS_DECLINE]
        );
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(
            "UPDATE setting SET setting_value = JSON_SET(setting_value, '$.pending.filters', JSON_OBJECT('status', ?)) WHERE setting_code = 'transaction.list.filters';",
            [Transaction::TRANSACTION_STATUS_START]
        );
    }
}
