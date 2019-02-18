<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181210050431 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE sub_transaction ADD subtransaction_virtual_immutable_username VARCHAR(255) AS (subtransaction_details->>"$.immutableCustomerProductData.username")');
        $this->addSql('CREATE INDEX virtual_immutable_username ON sub_transaction (subtransaction_virtual_immutable_username)');
        $this->addSql('ALTER TABLE transaction DROP INDEX date_index');
        $this->addSql('CREATE INDEX date_index ON transaction (transaction_date DESC)');
        $this->addSql('CREATE INDEX date_status_index ON transaction (transaction_status, transaction_date desc)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('DROP INDEX virtual_immutable_username ON sub_transaction');
        $this->addSql('ALTER TABLE sub_transaction DROP subtransaction_virtual_immutable_username');
        $this->addSql('ALTER TABLE transaction DROP INDEX date_index');
        $this->addSql('CREATE INDEX date_index ON transaction (transaction_date)');
        $this->addSql('ALTER TABLE transaction DROP INDEX date_status_index');
    }
}
