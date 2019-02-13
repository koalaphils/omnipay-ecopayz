<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181120094852 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX virtual_bitcoin_sender_address_index ON transaction');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_virtual_bitcoin_sender_address transaction_virtual_bitcoin_sender_address JSON AS (transaction_other_details->>"$.bitcoin.transaction.sender_address") COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE transaction CHANGE transaction_virtual_bitcoin_sender_address transaction_virtual_bitcoin_sender_address VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('CREATE INDEX virtual_bitcoin_sender_address_index ON transaction (transaction_virtual_bitcoin_sender_address)');
    }
}
