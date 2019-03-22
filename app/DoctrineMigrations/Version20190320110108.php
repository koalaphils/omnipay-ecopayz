<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190320110108 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql(file_get_contents(__DIR__ . '/defaultdata.sql'));
    }

    public function down(Schema $schema) : void
    {
    }
}
