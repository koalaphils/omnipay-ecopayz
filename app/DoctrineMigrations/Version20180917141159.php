<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180917141159 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM setting WHERE setting_code = ?', ['origin']);
        
        $origins = [
            'origins' => [
                [
                    'code' => 'EN',
                    'name' => 'https://www.asianconnect88.com',
                    'url' => 'https://www.asianconnect88.com/',
                ],
                [
                    'code' => 'EN',
                    'name' => 'https://asianconnect88.net',
                    'url' => 'https://asianconnect88.net/',
                ],
                [
                    'code' => 'EN',
                    'name' => 'https://www.asianconnect888.com',
                    'url' => 'https://www.asianconnect888.com/',
                ],
                [
                    'code' => 'FR',
                    'name' => 'https://fr.asianconnect88.com',
                    'url' => 'https://fr.asianconnect88.com/',
                ],
                [
                    'code' => 'FR',
                    'name' => 'https://ac0808.com',
                    'url' => 'https://ac0808.com/',
                ],
                [
                    'code' => 'FR',
                    'name' => 'https://ac0188.com',
                    'url' => 'https://ac0188.com/',
                ],
                [
                    'code' => 'FR',
                    'name' => 'https://ac0288.com',
                    'url' => 'https://ac0288.com/',
                ],
                [
                    'code' => 'DE',
                    'name' => 'https://de.asianconnect88.com',
                    'url' => 'https://de.asianconnect88.com/',
                ],
                [
                    'code' => 'DE',
                    'name' => 'https://www.asianconnectde.com',
                    'url' => 'https://www.asianconnectde.com/',
                ],
                [
                    'code' => 'ES',
                    'name' => 'https://www.asianconect88.com',
                    'url' => 'https://www.asianconect88.com/',
                ],
                [
                    'code' => 'ES',
                    'name' => 'http://www.asianconnect08.com',
                    'url' => 'http://www.asianconnect08.com/',
                ],
            ],
        ];

        $this->addSql('INSERT INTO setting (setting_code, setting_value) VALUES (?, ?)', ['origin', json_encode($origins)]);
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
