<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180807061620 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE transaction ADD transaction_commission_computed_original NUMERIC(65, 10) AS (transaction_other_details->"$.commission.computed.original")');
        $this->addSql('ALTER TABLE sub_transaction ADD subtransaction_dwl_turnover NUMERIC(65, 10) AS (subtransaction_details->"$.dwl.turnover"), ADD subtransaction_dwl_winloss NUMERIC(65, 10) AS (subtransaction_details->"$.dwl.winLoss")');
        $this->addSql('CREATE INDEX commission_computed_original_index ON transaction (transaction_commission_computed_original)');
        $this->addSql('CREATE INDEX dwl_turnover_index ON sub_transaction (subtransaction_dwl_turnover)');
        $this->addSql('CREATE INDEX dwl_winloss_index ON sub_transaction (subtransaction_dwl_winloss)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE transaction DROP transaction_commission_computed_original');
        $this->addSql('ALTER TABLE sub_transaction DROP subtransaction_dwl_turnover, DROP subtransaction_dwl_winloss');
        $this->addSql('DROP INDEX commission_computed_original_index ON transaction');
        $this->addSql('DROP INDEX dwl_turnover_index ON sub_transaction');
        $this->addSql('DROP INDEX dwl_winloss_index ON sub_transaction');
    }
}
