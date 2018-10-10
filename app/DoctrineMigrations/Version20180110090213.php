<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180110090213 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, CHANGE transaction_to_customer transaction_to_customer INT AS (transaction_other_details->"$.toCustomer"), CHANGE dwl_id dwl_id INT AS (transaction_other_details->"$.dwl.id")');
        $this->addSql('CREATE INDEX fname_index ON customer (customer_fname)');
        $this->addSql('CREATE INDEX lname_index ON customer (customer_lname)');
        $this->addSql('CREATE INDEX email_index ON user (user_email)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX fname_index ON customer');
        $this->addSql('DROP INDEX lname_index ON customer');
        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE transaction_to_customer transaction_to_customer INT DEFAULT NULL, CHANGE dwl_id dwl_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX email_index ON user');
    }
}
