<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180920084206 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX timestamp_index ON audit_revision (audit_revision_timestamp)');
        $this->addSql('ALTER TABLE audit_revision_log ADD audit_revision_log_class_name VARCHAR(255) AS (JSON_UNQUOTE(JSON_EXTRACT(audit_revision_log_details,\'$.class_name\'))), ADD audit_revision_log_identifier VARCHAR(255) AS (JSON_UNQUOTE(JSON_EXTRACT(audit_revision_log_details,\'$.identifier\'))), ADD audit_revision_log_label VARCHAR(255) AS (JSON_UNQUOTE(JSON_EXTRACT(audit_revision_log_details,\'$.label\')))');
        $this->addSql('CREATE INDEX operation_index ON audit_revision_log (audit_revision_log_operation)');
        $this->addSql('CREATE INDEX category_index ON audit_revision_log (audit_revision_log_category)');
        $this->addSql('CREATE INDEX identifier_index ON audit_revision_log (audit_revision_log_identifier)');
        $this->addSql('CREATE INDEX className_index ON audit_revision_log (audit_revision_log_class_name)');
        $this->addSql('CREATE INDEX label_index ON audit_revision_log (audit_revision_log_label)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX timestamp_index ON audit_revision');
        $this->addSql('DROP INDEX operation_index ON audit_revision_log');
        $this->addSql('DROP INDEX category_index ON audit_revision_log');
        $this->addSql('DROP INDEX identifier_index ON audit_revision_log');
        $this->addSql('DROP INDEX className_index ON audit_revision_log');
        $this->addSql('DROP INDEX label_index ON audit_revision_log');
        $this->addSql('ALTER TABLE audit_revision_log DROP audit_revision_log_class_name, DROP audit_revision_log_identifier, DROP audit_revision_log_label');
    }
}
