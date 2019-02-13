<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180830051045 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE IF NOT EXISTS inactive_member (inactive_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, member_id BIGINT UNSIGNED NOT NULL, inactive_member_added_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_4470EE2F7597D3FE (member_id), PRIMARY KEY(inactive_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE inactive_member ADD CONSTRAINT FK_4470EE2F7597D3FE FOREIGN KEY (member_id) REFERENCES customer (customer_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE inactive_member');
    }
}
