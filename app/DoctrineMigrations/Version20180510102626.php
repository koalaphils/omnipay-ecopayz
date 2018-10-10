<?php declare(strict_types = 1);

namespace Application\Migrations;

use DbBundle\Entity\BannerImage;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180510102626 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $languages = ['EN', 'FR', 'DE', 'ES', 'AO'];
        $sizes = ['728x90', '468x60', '300x300', '300x250', '250x250'];
        $types = [BannerImage::TYPE_PROMOTION, BannerImage::TYPE_ADVERTISEMENT];
        $filenames = [
            'EN' => [
                BannerImage::TYPE_ADVERTISEMENT => 'acad',
                BannerImage::TYPE_PROMOTION => 'acwelcome',
            ],
            'AO' => [
                BannerImage::TYPE_ADVERTISEMENT => 'ao-ad',
                BannerImage::TYPE_PROMOTION => 'ao-acwelcome',
            ],
            'FR' => [
                BannerImage::TYPE_ADVERTISEMENT => 'fr-ad',
                BannerImage::TYPE_PROMOTION => 'fr-acwelcome',
            ],
            'DE' => [
                BannerImage::TYPE_ADVERTISEMENT => 'de-ad',
                BannerImage::TYPE_PROMOTION => 'de-acwelcome',
            ],
            'ES' => [
                BannerImage::TYPE_ADVERTISEMENT => 'es-ad',
                BannerImage::TYPE_PROMOTION => 'es-acwelcome',
            ],
        ];

        foreach ($languages as $language) {
            foreach ($sizes as $size) {
                foreach ($types as $type) {
                    $filename = sprintf('%s-%s.jpg', $filenames[$language][$type], $size);

                    $this->addSql('INSERT INTO banner_image(
                        banner_image_language, banner_image_type, banner_image_dimension, 
                        banner_image_details, banner_image_is_active, banner_image_created_by, banner_image_created_at
                      ) VALUES(?, ?, ?, ?, ?, ?, NOW())', [$language, $type, $size, json_encode(['filename' => $filename]), true, 1]);
                }
            }
        }
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('TRUNCATE TABLE banner_image;');
    }
}
