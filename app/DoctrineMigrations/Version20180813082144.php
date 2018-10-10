<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180813082144 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('UPDATE setting 
                          SET setting_value = JSON_SET(setting_value, "$.links.ES.urls.promotion", ?) 
                          WHERE setting_code = ?',
            ['https://www.asianconect88.com/promociones/', 'referral.tools']);
        $this->addSql('UPDATE setting 
                          SET setting_value = JSON_SET(setting_value, "$.links.ES.urls.advertisement", ?) 
                          WHERE setting_code = ?',
            ['https://www.asianconect88.com/registro/', 'referral.tools']);
        $this->addSql('UPDATE setting 
                          SET setting_value = JSON_SET(setting_value, "$.links.AO.urls.promotion", ?) 
                          WHERE setting_code = ?',
            ['https://www.asianodds88.com/register-promo.aspx/', 'referral.tools']);
        $this->addSql('UPDATE setting 
                          SET setting_value = JSON_SET(setting_value, "$.links.AO.urls.advertisement", ?) 
                          WHERE setting_code = ?',
            ['https://www.asianodds88.com/register.aspx/', 'referral.tools']);
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
