<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180214125202 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer_product ADD cproduct_bet_sync_id BIGINT AS (cproduct_details->"$.brokerage.sync_id")');
        $this->addSql('CREATE INDEX betSyncId_index ON customer_product (cproduct_bet_sync_id)');
        $this->addSql('ALTER TABLE transaction ADD transaction_bet_id BIGINT AS (transaction_other_details->"$.bet_id"), ADD transaction_bet_event_id BIGINT AS (transaction_other_details->"$.event_id")');
        $this->addSql('CREATE INDEX bet_id_index ON transaction (transaction_bet_id)');
        $this->addSql('CREATE INDEX bet_event_id_index ON transaction (transaction_bet_event_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX betSyncId_index ON customer_product');
        $this->addSql('ALTER TABLE customer_product DROP cproduct_sync_id');
        $this->addSql('DROP INDEX bet_id_index ON transaction');
        $this->addSql('DROP INDEX bet_event_id_index ON transaction');
        $this->addSql('ALTER TABLE transaction DROP transaction_bet_id, DROP transaction_bet_event_id');
    }
}
