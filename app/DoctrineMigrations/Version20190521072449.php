<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190521072449 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE gateway_log ADD gateway_log_reference_class VARCHAR(255) AS (gateway_log_details->>"$.reference_class"), ADD gateway_log_reference_identifier VARCHAR(255) AS (gateway_log_details->>"$.identifier")');
        $this->addSql('CREATE INDEX reference_class_index ON gateway_log (gateway_log_reference_class)');
        $this->addSql('CREATE INDEX reference_indentifier_index ON gateway_log (gateway_log_reference_identifier)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX reference_class_index ON gateway_log');
        $this->addSql('DROP INDEX reference_indentifier_index ON gateway_log');
        $this->addSql('ALTER TABLE gateway_log DROP gateway_log_reference_class, DROP gateway_log_reference_identifier');
    }
}
