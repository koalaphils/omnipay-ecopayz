<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180502061759 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE customer CHANGE customer_fname customer_fname VARCHAR(64) DEFAULT \'\', CHANGE customer_mname customer_mname VARCHAR(64) DEFAULT \'\', CHANGE customer_lname customer_lname VARCHAR(64) DEFAULT \'\'');
        $this->addSql('CREATE INDEX fullname_index ON customer (customer_full_name)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX fullname_index ON customer');
        $this->addSql('ALTER TABLE customer CHANGE customer_fname customer_fname VARCHAR(64) NOT NULL COLLATE utf8_unicode_ci, CHANGE customer_mname customer_mname VARCHAR(64) DEFAULT \'\' NOT NULL COLLATE utf8_unicode_ci, CHANGE customer_lname customer_lname VARCHAR(64) NOT NULL COLLATE utf8_unicode_ci');
    }
}
