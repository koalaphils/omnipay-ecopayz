<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180515094000 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $referralData = [
            'piwikUrlKey' => 'pk_kwd',
            'links' => [
                'EN' => [
                    'siteId' => 2,
                    'urls' => [
                        'promotion' => 'https://www.asianconnect88.com/promotion/acwelcome400-terms-and-conditions/',
                        'advertisement' => 'https://www.asianconnect88.com/register/',
                    ]
                ],
                'FR' => [
                    'siteId' => 18,
                    'urls' => [
                        'promotion' => 'https://ac0188.com/inscription/',
                        'advertisement' => 'https://ac0188.com/inscription/',
                    ]
                ],
                'DE' => [
                    'siteId' => 21,
                    'urls' => [
                        'promotion' => 'https://de.asianconnect88.com/promotion/acwelcome400-geschaftsbedingungen/',
                        'advertisement' => 'https://de.asianconnect88.com/register/',
                    ]
                ],
                'ES' => [
                    'siteId' => 22,
                    'urls' => [
                        'promotion' => 'https://es.asianconnect88.com/acwelcome400-terminos-y-condiciones/',
                        'advertisement' => 'https://es.asianconnect88.com/registro/',
                    ]
                ],
                'AO' => [
                    'siteId' => 3,
                    'urls' => [
                        'promotion' => 'https://asianodds88.com/promotion/',
                        'advertisement' => 'https://asianodds88.com/register/',
                    ]
                ],
            ]
        ];

        $this->addSql('INSERT INTO setting(setting_code, setting_value) VALUES(?, ?)', ['referral.tools', json_encode($referralData)]);
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM setting WHERE setting_code = ?', ['referral.tools']);
    }
}
