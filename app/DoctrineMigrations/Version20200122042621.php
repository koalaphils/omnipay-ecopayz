<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200122042621 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
    	$this->addSql('CREATE TABLE member_request (member_request_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, member_request_member_id BIGINT UNSIGNED DEFAULT NULL, member_request_number VARCHAR(255) NOT NULL, member_request_date DATETIME NOT NULL, member_request_type INT UNSIGNED NOT NULL, member_request_status INT UNSIGNED NOT NULL, member_request_details JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', member_request_created_by BIGINT UNSIGNED NOT NULL, member_request_created_at DATETIME NOT NULL, member_request_updated_by BIGINT UNSIGNED DEFAULT NULL, member_request_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NULL, UNIQUE INDEX UNIQ_8463380B3924051C (member_request_number), INDEX IDX_8463380B773F8267 (member_request_member_id), INDEX UNIQ_20190328103500 (member_request_number), PRIMARY KEY(member_request_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    	$this->addSql('ALTER TABLE member_request ADD CONSTRAINT FK_8463380B773F8267 FOREIGN KEY (member_request_member_id) REFERENCES customer (customer_id)');
    	$this->addSql('ALTER TABLE `member_request` ADD INDEX `member_request_type` (`member_request_type`)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
    	$this->addSql('
            SET FOREIGN_KEY_CHECKS=0;
            DROP TABLE IF EXISTS `member_request`;
            SET FOREIGN_KEY_CHECKS=1;
        ');
    }
}
