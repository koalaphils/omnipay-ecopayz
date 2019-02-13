<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20181001083302 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE transaction ADD transaction_virtual_bitcoin_transaction_hash VARCHAR(255) AS (transaction_other_details->>"$.bitcoin.transaction.hash")');
        $this->addSql('CREATE INDEX virtual_bitcoin_transaction_hash_index ON transaction (transaction_virtual_bitcoin_transaction_hash)');
        $this->addSql('ALTER TABLE transaction ADD transaction_bitcoin_confirmation_count INT AS (transaction_other_details->>"$.bitcoin.confirmation_count")');
        $this->addSql('CREATE INDEX bitcoin_confirmation_count_index ON transaction (transaction_bitcoin_confirmation_count)');
        $this->addSql('ALTER TABLE transaction ADD transaction_virtual_bitcoin_sender_address VARCHAR(255) AS (transaction_other_details->>"$.bitcoin.transaction.sender_address")');
        $this->addSql('CREATE INDEX virtual_bitcoin_sender_address_index ON transaction (transaction_virtual_bitcoin_sender_address)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DROP INDEX virtual_bitcoin_transaction_hash_index ON transaction');
        $this->addSql('ALTER TABLE transaction DROP transaction_virtual_bitcoin_transaction_hash');
        $this->addSql('DROP INDEX bitcoin_confirmation_count_index ON transaction');
        $this->addSql('ALTER TABLE transaction DROP transaction_bitcoin_confirmation_count');
        $this->addSql('DROP INDEX virtual_bitcoin_sender_address_index ON transaction');
        $this->addSql('ALTER TABLE transaction DROP transaction_virtual_bitcoin_sender_address');
    }
}
