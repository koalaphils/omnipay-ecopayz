<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180416031534 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE member_commission DROP FOREIGN KEY FK_F72B1FB1F1F6407A');
        $this->addSql('DROP INDEX IDX_F72B1FB1F1F6407A ON member_commission');
        $this->addSql('ALTER TABLE member_commission DROP member_commission_currency_id');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE member_commission ADD member_commission_currency_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE member_commission ADD CONSTRAINT FK_F72B1FB1F1F6407A FOREIGN KEY (member_commission_currency_id) REFERENCES currency (currency_id)');
        $this->addSql('CREATE INDEX IDX_F72B1FB1F1F6407A ON member_commission (member_commission_currency_id)');
    }
}
