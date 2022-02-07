<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220110114553 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs 

        $this->addSql('CREATE TABLE promo (
            promo_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            promo_code VARCHAR(50) NOT NULL,
            promo_name VARCHAR(255) NOT NULL,
            promo_status TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            promo_details JSON NULL COMMENT \'(DC2Type:json)\',
            promo_created_by BIGINT UNSIGNED NOT NULL,
            promo_created_at DATETIME NOT NULL,
            promo_updated_by BIGINT UNSIGNED DEFAULT NULL,
            promo_updated_at DATETIME DEFAULT NULL,
            INDEX promo_index (promo_status),
            UNIQUE INDEX UNIQ_B0139AFB14BADD00 (promo_name),
            UNIQUE INDEX UNIQ_B0139AFB3D8C939E (promo_code)
          ) ENGINE=\'InnoDB\' COLLATE \'utf8_unicode_ci\'');
  
          $this->addSql("INSERT INTO promo (promo_code, promo_name, promo_status, promo_details, promo_created_by, promo_created_at, promo_updated_by, promo_updated_at) 
              VALUES ('REFERAFRIEND', 'Refer a friend', '1', '{\"conditions\": [{\"url\": \"https://www.piwi143.com\", \"exempted\": [\"GB\", \"RU\", \"UA\", \"IN\", \"BY\", \"MD\", \"AZ\", \"TH\", \"CN\", \"HK\", \"MY\", \"SG\", \"TW\", \"MO\"], \"countries\": [\"ALL\"], \"isDefault\": true, \"currencies\": [\"EUR\"], \"isLinkShortened\": true}]}', '25106', now(), NULL, now())");
  
          $this->addSql('CREATE TABLE member_promo (
            member_promo_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            member_promo_promo_id BIGINT UNSIGNED NOT NULL,
            member_promo_referrer_id BIGINT(20) UNSIGNED NOT NULL,
            member_promo_member_id BIGINT(20) UNSIGNED NOT NULL,
            member_promo_transaction_id BIGINT(20) UNSIGNED DEFAULT NULL,
            member_promo_created_by BIGINT UNSIGNED NOT NULL,
            member_promo_created_at DATETIME NOT NULL,
            member_promo_updated_by BIGINT UNSIGNED DEFAULT NULL,
            member_promo_updated_at DATETIME DEFAULT NULL,
            INDEX IDX_D2320F08FCF28674 (member_promo_promo_id),
            INDEX IDX_D2320F08E43229A7 (member_promo_referrer_id),
            INDEX IDX_D2320F08FD1BBAA (member_promo_member_id),
            INDEX mp_transaction_index (member_promo_transaction_id),
            UNIQUE INDEX UNIQ_D2320F08BD061DE6 (member_promo_transaction_id)
          ) ENGINE=\'InnoDB\' COLLATE \'utf8_unicode_ci\'');
    }

    public function down(Schema $schema) : void
    {
        //this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE member_promo');
        $this->addSql('DROP TABLE promo');
    }
}
