<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200323072635 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `product` SET `product_name` = 'Sports' WHERE `product_code` = 'PINBET'");
        $this->addSql("UPDATE `product` SET `product_name` = 'Casino' WHERE `product_code` = 'EVOLUTION'");
        $this->addSql("UPDATE `product` SET `product_name` = 'Member Wallet' WHERE `product_code` = 'PWM'");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("UPDATE `product` SET `product_name` = 'PIN BET' WHERE `product_code` = 'PINBET'");
        $this->addSql("UPDATE `product` SET `product_name` = 'Evolution' WHERE `product_code` = 'EVOLUTION'");
        $this->addSql("UPDATE `product` SET `product_name` = 'PIWI MEMBER WALLET' WHERE `product_code` = 'PWM'");
    }
}
