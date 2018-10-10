<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180116062718 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE transaction ADD transaction_payment_option_on_transaction_id BIGINT DEFAULT NULL, CHANGE transaction_updated_at transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CHANGE transaction_to_customer transaction_to_customer INT AS (transaction_other_details->"$.toCustomer"), CHANGE dwl_id dwl_id INT AS (transaction_other_details->"$.dwl.id")');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1682A26E5 FOREIGN KEY (transaction_payment_option_on_transaction_id) REFERENCES customer_payment_option (customer_payment_option_id)');
        $this->addSql('CREATE INDEX IDX_723705D1682A26E5 ON transaction (transaction_payment_option_on_transaction_id)');
        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE sub_transaction CHANGE subtransaction_dwl_id subtransaction_dwl_id BIGINT AS (subtransaction_details->"$.dwl.id")');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE sub_transaction CHANGE subtransaction_dwl_id subtransaction_dwl_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1682A26E5');
        $this->addSql('DROP INDEX IDX_723705D1682A26E5 ON transaction');
        $this->addSql('ALTER TABLE transaction DROP transaction_payment_option_on_transaction_id, CHANGE transaction_updated_at transaction_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE transaction_to_customer transaction_to_customer INT DEFAULT NULL, CHANGE dwl_id dwl_id INT DEFAULT NULL');
    }
}
