<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20181029115309 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('UPDATE product SET product_details = JSON_SET(product_details, "$.has_username", false)');
        $this->addSql('UPDATE product SET product_details = JSON_SET(product_details, "$.has_username", true) WHERE product_code IN (?, ?)', ['AO', 'ORB']);
        $this->addSql('UPDATE product SET product_details = JSON_SET(product_details, "$.can_be_requested", true)');
        $this->addSql('UPDATE product SET product_details = JSON_SET(product_details, "$.can_be_requested", false) WHERE product_code IN (?, ?)', ['UNF', 'ACW']);
        $this->addSql('UPDATE product SET product_details = JSON_SET(product_details, "$.has_terms", false)');
        $this->addSql('UPDATE product SET product_details = JSON_SET(product_details, "$.has_terms", true) WHERE product_code IN (?, ?, ?, ?, ?)', ['SBO', 'SIN', 'MXB', 'ORB', 'WIC']);
    }

    public function down(Schema $schema) : void
    {
    }
}
