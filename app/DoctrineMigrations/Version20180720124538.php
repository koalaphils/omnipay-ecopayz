<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180720124538 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE member_running_commission ADD member_running_commission_preceeding_id BIGINT UNSIGNED DEFAULT NULL, ADD member_running_commission_succeeding_id BIGINT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE member_running_commission ADD CONSTRAINT FK_D2E61304EE0614D3 FOREIGN KEY (member_running_commission_preceeding_id) REFERENCES member_running_commission (member_running_commission_id)');
        $this->addSql('ALTER TABLE member_running_commission ADD CONSTRAINT FK_D2E61304CFE15BD1 FOREIGN KEY (member_running_commission_succeeding_id) REFERENCES member_running_commission (member_running_commission_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D2E61304EE0614D3 ON member_running_commission (member_running_commission_preceeding_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D2E61304CFE15BD1 ON member_running_commission (member_running_commission_succeeding_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE member_running_commission DROP FOREIGN KEY FK_D2E61304EE0614D3');
        $this->addSql('ALTER TABLE member_running_commission DROP FOREIGN KEY FK_D2E61304CFE15BD1');
        $this->addSql('DROP INDEX UNIQ_D2E61304EE0614D3 ON member_running_commission');
        $this->addSql('DROP INDEX UNIQ_D2E61304CFE15BD1 ON member_running_commission');
        $this->addSql('ALTER TABLE member_running_commission DROP member_running_commission_preceeding_id, DROP member_running_commission_succeeding_id');
    }
}
