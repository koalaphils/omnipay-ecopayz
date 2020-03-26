<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200326052808 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql("UPDATE `product` SET `product_name` = 'Sports' WHERE `product_code` = 'PINBET'");
        $this->addSql("INSERT `product` VALUES(3, 'EVOLUTION', 'Casino', 1, NULL, NULL, 1, '2020-02-07 06:30:38', NULL, NULL, NULL, '{}' )");
        $this->addSql("INSERT `product` VALUES(4, 'PWM', 'Member Wallet', 1, NULL, NULL, 1, '2020-02-07 06:30:38', NULL, NULL, NULL, '{}'");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
