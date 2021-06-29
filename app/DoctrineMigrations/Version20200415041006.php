<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200415041006 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commission_period ADD commission_period_dwl_status TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE commission_period ADD commission_period_dwl_updated_at DATETIME NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
		$this->addSql('ALTER TABLE commission_period DROP commission_period_dwl_status');
		$this->addSql('ALTER TABLE commission_period DROP commission_period_dwl_updated_at');
    }
}
