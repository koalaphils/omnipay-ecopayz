<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180110033215 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CHANGE transaction_to_customer transaction_to_customer INT AS (transaction_other_details->"$.toCustomer"), CHANGE dwl_id dwl_id INT AS (transaction_other_details->"$.dwl.id")');
        $this->addSql('CREATE INDEX dwlId_index ON transaction (dwl_id)');
        $this->addSql('CREATE INDEX date_index ON dwl (dwl_date)');
        $this->addSql('ALTER TABLE sub_transaction ADD subtransaction_dwl_id BIGINT AS (subtransaction_details->"$.dwl.id")');
        $this->addSql('CREATE INDEX type_index ON sub_transaction (subtransaction_type)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX date_index ON dwl');
        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX type_index ON sub_transaction');
        $this->addSql('ALTER TABLE sub_transaction DROP subtransaction_dwl_id');
        $this->addSql('DROP INDEX dwlId_index ON transaction');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE transaction_to_customer transaction_to_customer INT DEFAULT NULL, CHANGE dwl_id dwl_id INT DEFAULT NULL');
    }
}