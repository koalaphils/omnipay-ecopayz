<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190416114138 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE transaction ADD transaction_virtual_bitcoin_is_acknowledge_by_member TINYINT(1) AS (IF(transaction_other_details->>"$.bitcoin.acknowledged_by_user" = \'true\', 1, 0))');
        $this->addSql('CREATE INDEX bitcoin_is_acknowledge_by_member ON transaction (transaction_virtual_bitcoin_is_acknowledge_by_member)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX bitcoin_is_acknowledge_by_member ON transaction');
        $this->addSql('ALTER TABLE transaction DROP transaction_virtual_bitcoin_is_acknowledge_by_member');
    }
}
