<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;


// Make sure to run affiliate service migration first before running this migration
final class Version20210527114741 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E0991CA0783');
        $this->addSql('ALTER TABLE customer RENAME COLUMN customer_affiliate_id TO old_customer_affiliate_id');
        $this->addSql('ALTER TABLE customer ADD customer_affiliate_id BIGINT');

        // Update the new customer_affiliate_id column equal to the affiliate user id.
        $this->addSql('
            UPDATE customer as member
            JOIN customer as affiliate
            ON affiliate.customer_id=member.old_customer_affiliate_id
            SET member.customer_affiliate_id=affiliate.customer_user_id
        ');
        

        // Create affiliate entry from customer table
        $this->addSql('
            INSERT INTO affiliates 
            (user_id, email, username, name, referral_codes, banners)
            SELECT user.user_id, user.user_email, user.user_username, affiliate.customer_fname, "[]", "[]" FROM customer as affiliate 
            JOIN user 
            ON user.user_id=affiliate.customer_user_id
            AND user.user_type=4
        ');

        // Create affiliate_member_mapping entry from customer table
        $this->addSql('
            INSERT INTO affiliate_member_mappings 
            (affiliate_user_id, member_user_id)
            SELECT affiliate_user.user_id, member.customer_user_id FROM customer as member 
            JOIN user as affiliate_user
            ON member.customer_affiliate_id=affiliate_user.user_id
            AND member.old_customer_affiliate_id IS NOT NULL
        ');
    }

    public function down(Schema $schema) : void
    {          
        $this->addSql('ALTER TABLE customer DROP customer_affiliate_id BIGINIT');
        $this->addSql('ALTER TABLE customer RENAME COLUMN old_customer_affiliate_id TO customer_affiliate_id');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E0991CA0783 FOREIGN KEY (customer_affiliate_id) REFERENCES customer (customer_id)'); 
    
        $this->addSql('DELETE FROM affiliate_member_mappings');
        $this->addSql('DELETE FROM affiliates');
    }
}