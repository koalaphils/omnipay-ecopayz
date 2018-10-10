<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171204065621 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE audit_revision (audit_revision_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, audit_revision_user_id BIGINT UNSIGNED DEFAULT NULL, audit_revision_timestamp DATETIME NOT NULL, audit_revision_client_ip VARCHAR(255) NOT NULL, INDEX IDX_3D774625AFD5FD7 (audit_revision_user_id), PRIMARY KEY(audit_revision_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audit_revision_log (audit_revision_log_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, audit_revision_log_audit_revision_id BIGINT UNSIGNED DEFAULT NULL, audit_revision_log_details JSON NOT NULL COMMENT \'(DC2Type:json)\', audit_revision_log_operation SMALLINT NOT NULL, audit_revision_log_category SMALLINT NOT NULL, INDEX IDX_19B97C61479B0D80 (audit_revision_log_audit_revision_id), PRIMARY KEY(audit_revision_log_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE audit_revision ADD CONSTRAINT FK_3D774625AFD5FD7 FOREIGN KEY (audit_revision_user_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE audit_revision_log ADD CONSTRAINT FK_19B97C61479B0D80 FOREIGN KEY (audit_revision_log_audit_revision_id) REFERENCES audit_revision (audit_revision_id)');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at DATETIME DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE audit_revision_log DROP FOREIGN KEY FK_19B97C61479B0D80');
        $this->addSql('DROP TABLE audit_revision');
        $this->addSql('DROP TABLE audit_revision_log');
        $this->addSql('ALTER TABLE gateway_transaction CHANGE gateway_transaction_updated_at gateway_transaction_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE transaction CHANGE transaction_updated_at transaction_updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
    }
}
