<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200316053525 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE winloss (winloss_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, winloss_affiliate BIGINT UNSIGNED DEFAULT NULL, winloss_created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', winloss_created_by BIGINT UNSIGNED NOT NULL, winloss_date DATE NOT NULL, winloss_member BIGINT UNSIGNED DEFAULT NULL, winloss_payout NUMERIC(65, 10) DEFAULT \'0.0000000000\' NOT NULL, winloss_period BIGINT UNSIGNED NOT NULL, winloss_pin_user_code VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, winloss_product BIGINT UNSIGNED DEFAULT NULL, winloss_status TINYINT(1) NOT NULL, winloss_turnover NUMERIC(65, 10) DEFAULT \'0.0000000000\' NOT NULL, winloss_updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime)\', winloss_updated_by BIGINT NOT NULL, UNIQUE INDEX winloss_date_winloss_pin_user_code_winloss_status (winloss_date, winloss_pin_user_code, winloss_status), PRIMARY KEY(winloss_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
    	$this->addSql('DROP TABLE winloss');
    }
}
