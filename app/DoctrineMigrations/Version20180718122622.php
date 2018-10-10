<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180718122622 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE currency_rate ADD currency_rate_destination_rate NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, CHANGE currency_rate_is_latest currency_rate_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE currency_rate ADD CONSTRAINT FK_555B7C4DA13148D9 FOREIGN KEY (currency_rate_created_by) REFERENCES user (user_id)');
        $this->addSql('CREATE INDEX IDX_555B7C4DA13148D9 ON currency_rate (currency_rate_created_by)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE currency_rate DROP FOREIGN KEY FK_555B7C4DA13148D9');
        $this->addSql('DROP INDEX IDX_555B7C4DA13148D9 ON currency_rate');
        $this->addSql('ALTER TABLE currency_rate DROP currency_rate_destination_rate, CHANGE currency_rate_is_latest currency_rate_is_latest TINYINT(1) DEFAULT \'1\' NOT NULL');
    }
}
