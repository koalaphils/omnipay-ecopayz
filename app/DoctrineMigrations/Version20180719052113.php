<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180719052113 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('CREATE TABLE IF NOT EXISTS commission_period (commission_period_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, commission_period_date_from DATE NOT NULL, commission_period_date_to DATE NOT NULL, commission_period_payout_at DATETIME NOT NULL, commission_period_status TINYINT DEFAULT \'1\' NOT NULL COMMENT \'(DC2Type:tinyint)\', commission_period_details JSON NOT NULL COMMENT \'(DC2Type:json)\', commission_period_conditions JSON NOT NULL COMMENT \'(DC2Type:json)\', commission_period_updated_by BIGINT UNSIGNED DEFAULT NULL, commission_period_updated_at DATETIME DEFAULT NULL, commission_period_created_by BIGINT UNSIGNED NOT NULL, commission_period_created_at DATETIME NOT NULL, PRIMARY KEY(commission_period_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE member_running_commission CHANGE member_running_commission_schedule_id member_running_commission_period_id BIGINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE member_running_commission ADD CONSTRAINT FK_D2E6130444523B87 FOREIGN KEY (member_running_commission_period_id) REFERENCES commission_period (commission_period_id)');
        $this->addSql('CREATE INDEX IDX_D2E6130444523B87 ON member_running_commission (member_running_commission_period_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE member_running_commission DROP FOREIGN KEY FK_D2E6130444523B87');
        $this->addSql('DROP TABLE commission_period');
        $this->addSql('DROP INDEX IDX_D2E6130444523B87 ON member_running_commission');
        $this->addSql('ALTER TABLE member_running_commission CHANGE member_running_commission_period_id member_running_commission_schedule_id BIGINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE member_running_commission ADD CONSTRAINT FK_D2E61304CEA9FD17 FOREIGN KEY (member_running_commission_schedule_id) REFERENCES commission_schedule (commission_schedule_id)');
        $this->addSql('CREATE INDEX IDX_D2E61304CEA9FD17 ON member_running_commission (member_running_commission_schedule_id)');
    }
}
