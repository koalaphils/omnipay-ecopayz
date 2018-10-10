<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180607124653 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE commission_schedule (commission_schedule_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, commission_schedule_date_from DATE NOT NULL, commission_schedule_date_to DATE NOT NULL, commission_schedule_at DATETIME NOT NULL, commission_schedule_executed TINYINT(1) DEFAULT \'0\' NOT NULL, commission_schedule_updated_by BIGINT UNSIGNED DEFAULT NULL, commission_schedule_updated_at DATETIME DEFAULT NULL, commission_schedule_created_by BIGINT UNSIGNED NOT NULL, commission_schedule_created_at DATETIME NOT NULL, PRIMARY KEY(commission_schedule_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE member_running_commission (member_running_commission_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, member_running_commission_transaction_id BIGINT UNSIGNED DEFAULT NULL, member_running_commission_cproduct_id BIGINT UNSIGNED DEFAULT NULL, member_running_commission_schedule_id BIGINT UNSIGNED DEFAULT NULL, member_running_commission_balance NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, member_running_commission_commission NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, member_running_commission_status VARCHAR(255) NOT NULL, member_running_commission_metadata JSON NOT NULL COMMENT \'(DC2Type:metadata)\', member_running_commission_updated_by BIGINT UNSIGNED DEFAULT NULL, member_running_commission_updated_at DATETIME DEFAULT NULL, member_running_commission_created_by BIGINT UNSIGNED NOT NULL, member_running_commission_created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D2E61304DDED61B3 (member_running_commission_transaction_id), INDEX IDX_D2E613046E1D703B (member_running_commission_cproduct_id), INDEX IDX_D2E61304CEA9FD17 (member_running_commission_schedule_id), PRIMARY KEY(member_running_commission_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE member_running_commission ADD CONSTRAINT FK_D2E61304DDED61B3 FOREIGN KEY (member_running_commission_transaction_id) REFERENCES transaction (transaction_id)');
        $this->addSql('ALTER TABLE member_running_commission ADD CONSTRAINT FK_D2E613046E1D703B FOREIGN KEY (member_running_commission_cproduct_id) REFERENCES customer_product (cproduct_id)');
        $this->addSql('ALTER TABLE member_running_commission ADD CONSTRAINT FK_D2E61304CEA9FD17 FOREIGN KEY (member_running_commission_schedule_id) REFERENCES commission_schedule (commission_schedule_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE member_running_commission DROP FOREIGN KEY FK_D2E61304CEA9FD17');
        $this->addSql('DROP TABLE commission_schedule');
        $this->addSql('DROP TABLE member_running_commission');
    }
}
