<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220325053916 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE member_tags (member_tags_member_id BIGINT UNSIGNED NOT NULL, member_tags_tag_id INT UNSIGNED NOT NULL, INDEX IDX_9334B87AF57B3411 (member_tags_member_id), INDEX IDX_9334B87A634CAE3 (member_tags_tag_id), PRIMARY KEY(member_tags_member_id, member_tags_tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE member_tag (member_tag_id INT UNSIGNED AUTO_INCREMENT NOT NULL, member_tag_name VARCHAR(64) NOT NULL, member_tag_description VARCHAR(255) NOT NULL, member_tag_is_delete_enabled TINYINT(1) DEFAULT \'1\' NOT NULL, member_tag_created_by BIGINT UNSIGNED NOT NULL, member_tag_created_at DATETIME NOT NULL, member_tag_updated_by BIGINT UNSIGNED DEFAULT NULL, member_tag_updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_322D97D847F1625A (member_tag_name), PRIMARY KEY(member_tag_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE member_tags ADD CONSTRAINT FK_9334B87AF57B3411 FOREIGN KEY (member_tags_member_id) REFERENCES customer (customer_id)');
        $this->addSql('ALTER TABLE member_tags ADD CONSTRAINT FK_9334B87A634CAE3 FOREIGN KEY (member_tags_tag_id) REFERENCES member_tag (member_tag_id)');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE member_tags DROP FOREIGN KEY FK_9334B87A634CAE3');
        $this->addSql('DROP TABLE member_tags');
        $this->addSql('DROP TABLE member_tag');
    }
}
