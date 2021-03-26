<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201112074811 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('UPDATE member_running_revenue_share SET member_running_revenue_share_metadata = JSON_SET(member_running_revenue_share_metadata, "$.correctRevenueShare", "-297.16") where member_running_revenue_share_id = "d6d76304-7d13-5edb-921b-80646a002674"');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
