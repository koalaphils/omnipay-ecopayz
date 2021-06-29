<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200204083254 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
		$this->addSql("UPDATE setting SET
		setting_value = '{\"1\": {\"label\": \"Requested\", \"start\": true, \"actions\": {\"3\": {\"class\": \"btn-success\", \"label\": \"Acknowledge\", \"status\": 4}, \"4\": {\"class\": \"btn-danger\", \"label\": \"Decline\", \"status\": 3}}, \"amsLabel\": \"Requested\", \"editDate\": false, \"editFees\": false, \"editAmount\": true, \"editRemark\": false, \"editGateway\": true, \"editBonusAmount\": false}, \"2\": {\"end\": true, \"label\": \"Processed\", \"actions\": [], \"amsLabel\": \"Processed\", \"editDate\": false, \"editFees\": false, \"editAmount\": false, \"editRemark\": false, \"editGateway\": false, \"editBonusAmount\": false}, \"3\": {\"label\": \"Declined\", \"actions\": [], \"decline\": true, \"amsLabel\": \"Declined\", \"editDate\": false, \"editFees\": false, \"editAmount\": false, \"editRemark\": false, \"editGateway\": false, \"editBonusAmount\": false}, \"4\": {\"label\": \"Acknowledged\", \"actions\": [{\"class\": \"btn-success\", \"label\": \"Process\", \"status\": 2}, {\"class\": \"btn-danger\", \"label\": \"Decline\", \"status\": 3}], \"amsLabel\": \"Acknowledged\", \"editDate\": true, \"editFees\": false, \"editAmount\": true, \"editRemark\": false, \"editRemark\": true, \"editGateway\": true, \"editBonusAmount\": false}}'
		WHERE setting_id = \"1\";");

    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
