<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190502130404 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('UPDATE `user` SET `user_roles` = \'{"ROLE_MEMBER": 2}\' WHERE `user_type` = 1');
    }

    public function down(Schema $schema) : void
    {
    }
}
