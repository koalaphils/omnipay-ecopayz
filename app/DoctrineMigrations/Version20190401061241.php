<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190401061241 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `transaction` DROP FOREIGN KEY FK_723705D1AA1251EB');
        $this->addSql('DROP INDEX IDX_723705D1AA1251EB ON `transaction`');
        $this->addSql('ALTER TABLE `transaction` DROP transaction_product_id');
        $this->addSql('ALTER TABLE `transaction` DROP transaction_popup');
        $this->addSql(file_get_contents(__DIR__ . '/Version20190401061241.sql'));
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE `transaction` ADD transaction_popup TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE `transaction` ADD transaction_product_id INT UNSIGNED NOT NULL');
        $this->addSql('ALTER TABLE `transaction` ADD CONSTRAINT FK_723705D1AA1251EB FOREIGN KEY (transaction_product_id) REFERENCES product (product_id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_723705D1AA1251EB ON `transaction` (transaction_product_id)');
    }
}
