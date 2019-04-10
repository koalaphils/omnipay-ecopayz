<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190405112458 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `transaction` DROP transaction_email');
        $this->addSql('ALTER TABLE `transaction` ADD transaction_email VARCHAR(255) AS (transaction_other_details->>"$.email")');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE `transaction` DROP transaction_email');
        $this->addSql('ALTER TABLE `transaction` ADD transaction_email VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('UPDATE `transaction` SET transaction_email = transaction_other_details->>"$.email"');
    }
}
