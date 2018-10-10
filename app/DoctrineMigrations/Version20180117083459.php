<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180117083459 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CHANGE transaction_to_customer transaction_to_customer INT AS (transaction_other_details->"$.toCustomer"), CHANGE dwl_id dwl_id INT AS (transaction_other_details->"$.dwl.id")');
        $this->addSql('ALTER TABLE transaction RENAME INDEX idx_723705d12f1cd6de TO customer_index');
        $this->addSql('CREATE INDEX username_index ON user (user_username)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE transaction_to_customer transaction_to_customer INT DEFAULT NULL, CHANGE dwl_id dwl_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE transaction RENAME INDEX customer_index TO IDX_723705D12F1CD6DE');
        $this->addSql('DROP INDEX username_index ON user');
    }
}
