<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211203154111 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
	    $this->addSql('ALTER TABLE winloss ADD winloss_pregenerated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
	    $this->addSql('ALTER TABLE winloss DROP winloss_pregenerated_at');
    }
}
