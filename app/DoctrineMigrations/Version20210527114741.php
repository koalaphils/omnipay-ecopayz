<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20210527114741 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE customer RENAME COLUMN customer_affiliate_id TO old_customer_affiliate_id');
        $this->addSql('ALTER TABLE customer ADD customer_affiliate_id BIGINIT');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE customer RENAME COLUMN old_customer_affiliate_id TO customer_affiliate_id');
        $this->addSql('ALTER TABLE customer DROP customer_affiliate_id BIGINIT');
    }
}
