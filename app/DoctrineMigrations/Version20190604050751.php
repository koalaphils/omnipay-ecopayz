<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Intl\Intl;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190604050751 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE country ADD country_locale VARCHAR(12) NOT NULL');
        $this->addSql('ALTER TABLE customer ADD customer_locale VARCHAR(12) NOT NULL');
        $this->addSql('UPDATE country SET country_locale = ?', ['en']);


        $availableLocales = ['en', 'zh_CN', 'zh_TW', 'id', 'vi', 'ja', 'ko', 'th', 'km', 'fr', 'de', 'es', 'he'];
        $locales = Intl::getRegionBundle()->getLocales();

        foreach ($locales as $locale) {
            $parts = explode('_', $locale);
            if (count($parts) > 1) {
                $lang = $parts[0];
                $country = $parts[1];
                if (in_array($locale, $availableLocales)) {
                    $this->addSql('UPDATE country SET country_locale = ? WHERE country_code = ?', [$locale, $country]);
                } elseif (in_array($lang, $availableLocales)) {
                    $this->addSql('UPDATE country SET country_locale = ? WHERE country_code = ?', [$lang, $country]);
                }
            }
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE country DROP country_locale');
        $this->addSql('ALTER TABLE customer DROP customer_locale');
    }
}
