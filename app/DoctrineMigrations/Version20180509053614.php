<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180509053614 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE member_website (member_website_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, member_website_member_id BIGINT UNSIGNED DEFAULT NULL, member_website_website VARCHAR(100) NOT NULL, member_website_is_active TINYINT(1) DEFAULT \'1\' NOT NULL, member_website_updated_by BIGINT UNSIGNED DEFAULT NULL, member_website_updated_at DATETIME DEFAULT NULL, member_website_created_by BIGINT UNSIGNED NOT NULL, member_website_created_at DATETIME NOT NULL, INDEX member_index (member_website_member_id), UNIQUE INDEX website_unq (member_website_website), PRIMARY KEY(member_website_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE member_referral_name (member_referral_name_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, member_referral_name_member_id BIGINT UNSIGNED DEFAULT NULL, member_referral_name_name VARCHAR(100) NOT NULL, member_referral_name_is_active TINYINT(1) DEFAULT \'1\' NOT NULL, member_referral_name_updated_by BIGINT UNSIGNED DEFAULT NULL, member_referral_name_updated_at DATETIME DEFAULT NULL, member_referral_name_created_by BIGINT UNSIGNED NOT NULL, member_referral_name_created_at DATETIME NOT NULL, INDEX member_index (member_referral_name_member_id), UNIQUE INDEX name_unq (member_referral_name_name), PRIMARY KEY(member_referral_name_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE banner_image (banner_image_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, banner_image_language VARCHAR(5) NOT NULL, banner_image_type SMALLINT NOT NULL, banner_image_dimension VARCHAR(50) NOT NULL, banner_image_details JSON NOT NULL COMMENT \'(DC2Type:json)\', banner_image_is_active TINYINT(1) DEFAULT \'1\' NOT NULL, banner_image_updated_by BIGINT UNSIGNED DEFAULT NULL, banner_image_updated_at DATETIME DEFAULT NULL, banner_image_created_by BIGINT UNSIGNED NOT NULL, banner_image_created_at DATETIME NOT NULL, INDEX type_index (banner_image_type), INDEX language_index (banner_image_language), INDEX dimension_index (banner_image_dimension), PRIMARY KEY(banner_image_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE member_banner (member_banner_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, member_banner_website_id BIGINT UNSIGNED DEFAULT NULL, member_banner_referral_name_id BIGINT UNSIGNED DEFAULT NULL, member_banner_image_id BIGINT UNSIGNED DEFAULT NULL, member_banner_member_id BIGINT UNSIGNED DEFAULT NULL, member_banner_campaign_name VARCHAR(100) NOT NULL, member_banner_created_by BIGINT UNSIGNED NOT NULL, member_banner_created_at DATETIME NOT NULL, INDEX IDX_4B4B3AD46D10E4A1 (member_banner_image_id), INDEX member_index (member_banner_member_id), INDEX referral_name_index (member_banner_referral_name_id), INDEX website_index (member_banner_website_id), UNIQUE INDEX campaign_name_unq (member_banner_campaign_name), PRIMARY KEY(member_banner_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE member_website ADD CONSTRAINT FK_F89BEA73AF169B14 FOREIGN KEY (member_website_member_id) REFERENCES customer (customer_id)');
        $this->addSql('ALTER TABLE member_referral_name ADD CONSTRAINT FK_AE67B3582E9B4996 FOREIGN KEY (member_referral_name_member_id) REFERENCES customer (customer_id)');
        $this->addSql('ALTER TABLE member_banner ADD CONSTRAINT FK_4B4B3AD493D86653 FOREIGN KEY (member_banner_website_id) REFERENCES member_website (member_website_id)');
        $this->addSql('ALTER TABLE member_banner ADD CONSTRAINT FK_4B4B3AD41D404BBA FOREIGN KEY (member_banner_referral_name_id) REFERENCES member_referral_name (member_referral_name_id)');
        $this->addSql('ALTER TABLE member_banner ADD CONSTRAINT FK_4B4B3AD46D10E4A1 FOREIGN KEY (member_banner_image_id) REFERENCES banner_image (banner_image_id)');
        $this->addSql('ALTER TABLE member_banner ADD CONSTRAINT FK_4B4B3AD4E715E8A4 FOREIGN KEY (member_banner_member_id) REFERENCES customer (customer_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE member_banner DROP FOREIGN KEY FK_4B4B3AD493D86653');
        $this->addSql('ALTER TABLE member_banner DROP FOREIGN KEY FK_4B4B3AD41D404BBA');
        $this->addSql('ALTER TABLE member_banner DROP FOREIGN KEY FK_4B4B3AD46D10E4A1');
        $this->addSql('DROP TABLE member_website');
        $this->addSql('DROP TABLE member_referral_name');
        $this->addSql('DROP TABLE banner_image');
        $this->addSql('DROP TABLE member_banner');
    }
}
