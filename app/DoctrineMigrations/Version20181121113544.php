<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181121113544 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
      $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
      $this->addSql('ALTER TABLE transaction ADD transaction_virtual_bitcoin_receiver_unique_address VARCHAR(255) AS (transaction_other_details->>"$.bitcoin.receiver_unique_address")');
      $this->addSql('CREATE INDEX virtual_bitcoin_receiver_unique_address ON transaction (transaction_virtual_bitcoin_receiver_unique_address)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
      $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
      $this->addSql('DROP INDEX virtual_bitcoin_receiver_unique_address ON transaction');
      $this->addSql('ALTER TABLE transaction DROP transaction_virtual_bitcoin_receiver_unique_address');
    }
}
