<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180830090210 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE inactive_member DROP FOREIGN KEY FK_4470EE2F7597D3FE');
        $this->addSql('DROP INDEX UNIQ_4470EE2F7597D3FE ON inactive_member');
        $this->addSql('ALTER TABLE inactive_member ADD inactive_member_id BIGINT UNSIGNED DEFAULT NULL, DROP member_id');
        $this->addSql('ALTER TABLE inactive_member ADD CONSTRAINT FK_4470EE2FC5A86BF7 FOREIGN KEY (inactive_member_id) REFERENCES customer (customer_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4470EE2FC5A86BF7 ON inactive_member (inactive_member_id)');
        $this->addSql('CREATE INDEX member_index ON inactive_member (inactive_member_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE inactive_member DROP FOREIGN KEY FK_4470EE2FC5A86BF7');
        $this->addSql('DROP INDEX UNIQ_4470EE2FC5A86BF7 ON inactive_member');
        $this->addSql('DROP INDEX member_index ON inactive_member');
        $this->addSql('ALTER TABLE inactive_member ADD member_id BIGINT UNSIGNED NOT NULL, DROP inactive_member_id');
        $this->addSql('ALTER TABLE inactive_member ADD CONSTRAINT FK_4470EE2F7597D3FE FOREIGN KEY (member_id) REFERENCES customer (customer_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4470EE2F7597D3FE ON inactive_member (member_id)');
    }
}
