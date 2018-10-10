<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180713102536 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $origins = [
            'origins' => [
                [
                    'code' => 'EN',
                    'name' => 'English Site 88.com',
                    'url' => 'https://www.asianconnect88.com/',
                ],
                [
                    'code' => 'EN',
                    'name' => 'English Site 88.net',
                    'url' => 'https://asianconnect88.net/',
                ],
                [
                    'code' => 'EN',
                    'name' => 'English Site 888.com',
                    'url' => 'https://www.asianconnect888.com/',
                ],
                [
                    'code' => 'FR',
                    'name' => 'French Site ac0808.com',
                    'url' => 'https://ac0808.com/',
                ],
                [
                    'code' => 'FR',
                    'name' => 'French Site ac0188.com',
                    'url' => 'https://ac0188.com/',
                ],
                [
                    'code' => 'FR',
                    'name' => 'French Site ac0288.com',
                    'url' => 'https://ac0288.com/',
                ],
                [
                    'code' => 'DE',
                    'name' => 'German Site',
                    'url' => 'https://de.asianconnect88.com/',
                ],
                [
                    'code' => 'ES',
                    'name' => 'Spanish Site',
                    'url' => 'https://www.asianconect88.com/',
                ],
                [
                    'code' => 'ES',
                    'name' => 'Spanish Site',
                    'url' => 'http://www.asianconnect08.com/',
                ],
            ],
        ];

        $this->addSql('INSERT INTO setting (setting_code, setting_value) VALUES (?, ?)', ['origin', json_encode($origins)]);
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DELETE FROM setting WHERE setting_code = "origin"');
    }
}
