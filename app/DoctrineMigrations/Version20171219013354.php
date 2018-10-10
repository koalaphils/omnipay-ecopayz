<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171219013354 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE gateway_transaction (gateway_transaction_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, gateway_transaction_gateway_id INT UNSIGNED DEFAULT NULL, gateway_transaction_gateway_to_id INT UNSIGNED DEFAULT NULL, gateway_transaction_currency_id INT UNSIGNED NOT NULL, gateway_transaction_payment_option_code VARCHAR(16) DEFAULT NULL, gateway_transaction_number VARCHAR(255) NOT NULL, gateway_transaction_type SMALLINT NOT NULL, gateway_transaction_date DATETIME NOT NULL, gateway_transaction_amount NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, gateway_transaction_fees JSON NOT NULL COMMENT \'(DC2Type:json)\', gateway_transaction_status SMALLINT UNSIGNED NOT NULL, gateway_transaction_details JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', gateway_transaction_is_voided TINYINT(1) DEFAULT \'0\' NOT NULL, gateway_transaction_created_by BIGINT UNSIGNED NOT NULL, gateway_transaction_created_at DATETIME NOT NULL, gateway_transaction_updated_by BIGINT UNSIGNED DEFAULT NULL, gateway_transaction_updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_4136A3411F9E9724 (gateway_transaction_number), INDEX IDX_4136A341D98172AE (gateway_transaction_gateway_id), INDEX IDX_4136A3417D60C973 (gateway_transaction_gateway_to_id), INDEX IDX_4136A3419C4F165 (gateway_transaction_currency_id), INDEX IDX_4136A341FDCE26F6 (gateway_transaction_payment_option_code), PRIMARY KEY(gateway_transaction_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transaction (transaction_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, transaction_created_by BIGINT UNSIGNED DEFAULT NULL, transaction_customer_id BIGINT UNSIGNED DEFAULT NULL, transaction_gateway_id INT UNSIGNED DEFAULT NULL, transaction_currency_id INT UNSIGNED NOT NULL, transaction_payment_option_id BIGINT DEFAULT NULL, transaction_payment_option_type VARCHAR(16) DEFAULT NULL, transaction_number VARCHAR(255) NOT NULL, transaction_amount NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, transaction_fees JSON NOT NULL COMMENT \'(DC2Type:json)\', transaction_type SMALLINT NOT NULL, transaction_date DATETIME NOT NULL, transaction_status SMALLINT UNSIGNED NOT NULL, transaction_is_voided TINYINT(1) DEFAULT \'0\' NOT NULL, transaction_other_details JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', transaction_created_at DATETIME NOT NULL, transaction_updated_by BIGINT UNSIGNED DEFAULT NULL, transaction_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, transaction_deleted_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_723705D1E0ED6D14 (transaction_number), INDEX IDX_723705D12F1CD6DE (transaction_customer_id), INDEX IDX_723705D1B8E9B9B1 (transaction_gateway_id), INDEX IDX_723705D184AD945B (transaction_currency_id), INDEX IDX_723705D1ABCAC298 (transaction_payment_option_id), INDEX IDX_723705D1294149FB (transaction_payment_option_type), INDEX IDX_723705D131849CE7 (transaction_created_by), INDEX type_index (transaction_type), INDEX date_index (transaction_date), INDEX status (transaction_status), INDEX isVoided_index (transaction_is_voided), INDEX createdAt_index (transaction_created_at), INDEX updatedAt_index (transaction_updated_at), PRIMARY KEY(transaction_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE country (country_id INT UNSIGNED AUTO_INCREMENT NOT NULL, country_currency_id INT UNSIGNED DEFAULT NULL, country_code VARCHAR(6) NOT NULL, country_name VARCHAR(64) NOT NULL, tags JSON NOT NULL COMMENT \'(DC2Type:json)\', country_created_by BIGINT UNSIGNED NOT NULL, country_created_at DATETIME NOT NULL, country_updated_by BIGINT UNSIGNED DEFAULT NULL, country_updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_5373C966F026BB7C (country_code), UNIQUE INDEX UNIQ_5373C966D910F5E2 (country_name), INDEX IDX_5373C9662DB3ABBD (country_currency_id), PRIMARY KEY(country_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer_product (cproduct_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, cproduct_customer_id BIGINT UNSIGNED NOT NULL, cproduct_product_id INT UNSIGNED NOT NULL, cproduct_username VARCHAR(100) NOT NULL, cproduct_balance NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, cproduct_is_active TINYINT(1) DEFAULT \'1\' NOT NULL, cproduct_created_by BIGINT UNSIGNED NOT NULL, cproduct_created_at DATETIME NOT NULL, cproduct_updated_by BIGINT UNSIGNED DEFAULT NULL, cproduct_updated_at DATETIME DEFAULT NULL, cproduct_details JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_CF97A013A32A23C6 (cproduct_product_id), INDEX IDX_CF97A0136ACAB2D9 (cproduct_customer_id), UNIQUE INDEX username_unq (cproduct_username, cproduct_product_id), PRIMARY KEY(cproduct_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer_group (customer_group_id INT UNSIGNED AUTO_INCREMENT NOT NULL, customer_group_name VARCHAR(64) NOT NULL, customer_is_default TINYINT(1) DEFAULT \'1\' NOT NULL, customer_group_created_by BIGINT UNSIGNED NOT NULL, customer_group_created_at DATETIME NOT NULL, customer_group_updated_by BIGINT UNSIGNED DEFAULT NULL, customer_group_updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_A3F531FE8AB021F4 (customer_group_name), PRIMARY KEY(customer_group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_client (id INT AUTO_INCREMENT NOT NULL, random_id VARCHAR(255) NOT NULL, redirect_uris LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', secret VARCHAR(255) NOT NULL, allowed_grant_types LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer_payment_option (customer_payment_option_id BIGINT AUTO_INCREMENT NOT NULL, customer_payment_option_type VARCHAR(16) DEFAULT NULL, customer_payment_options_customer_id BIGINT UNSIGNED DEFAULT NULL, customer_payment_option_is_active TINYINT(1) NOT NULL, customer_payment_option_fields JSON NOT NULL COMMENT \'(DC2Type:json)\', customer_payment_option_created_by BIGINT UNSIGNED DEFAULT NULL, customer_payment_option_created_at DATETIME NOT NULL, customer_payment_option_updated_by BIGINT UNSIGNED DEFAULT NULL, customer_payment_option_updated_at DATETIME DEFAULT NULL, INDEX IDX_E6D3AFECAE3CDBBC (customer_payment_option_type), INDEX IDX_E6D3AFEC2CB77FFD (customer_payment_options_customer_id), PRIMARY KEY(customer_payment_option_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer (customer_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, customer_user_id BIGINT UNSIGNED NOT NULL, customer_currency_id INT UNSIGNED NOT NULL, customer_country_id INT UNSIGNED DEFAULT NULL, customer_affiliate_id BIGINT UNSIGNED DEFAULT NULL, customer_fname VARCHAR(64) NOT NULL, customer_mname VARCHAR(64) DEFAULT \'\' NOT NULL, customer_lname VARCHAR(64) NOT NULL, customer_is_customer TINYINT(1) DEFAULT \'1\' NOT NULL, customer_is_affiliate TINYINT(1) DEFAULT \'0\' NOT NULL, customer_birthdate DATE DEFAULT NULL, customer_balance NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, customer_socials JSON NOT NULL COMMENT \'(DC2Type:json)\', customer_transaction_password VARCHAR(255) NOT NULL, customer_level INT DEFAULT 1 NOT NULL, customer_verified_at DATETIME DEFAULT NULL, customer_other_details JSON NOT NULL COMMENT \'(DC2Type:json)\', customer_joined_at DATETIME NOT NULL, customer_files JSON NOT NULL COMMENT \'(DC2Type:json)\', customer_contacts JSON NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_81398E09BBB3772B (customer_user_id), INDEX IDX_81398E093B6FAA7E (customer_currency_id), INDEX IDX_81398E094E63AF2 (customer_country_id), INDEX IDX_81398E0991CA0783 (customer_affiliate_id), PRIMARY KEY(customer_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer_groups (ccg_customer_id BIGINT UNSIGNED NOT NULL, ccg_customer_group_id INT UNSIGNED NOT NULL, INDEX IDX_41A8E521B151EDEB (ccg_customer_id), INDEX IDX_41A8E52181036024 (ccg_customer_group_id), PRIMARY KEY(ccg_customer_id, ccg_customer_group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_option (payment_option_code VARCHAR(16) NOT NULL, payment_option_name VARCHAR(64) NOT NULL, payment_mode VARCHAR(64) DEFAULT \'\' NOT NULL, payment_option_fields JSON NOT NULL COMMENT \'(DC2Type:json)\', payment_option_image_uri VARCHAR(255) DEFAULT NULL, payment_option_created_by BIGINT UNSIGNED DEFAULT NULL, payment_option_created_at DATETIME NOT NULL, payment_option_updated_by BIGINT UNSIGNED DEFAULT NULL, payment_option_updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_7FBE9B2640A1043F (payment_option_name), PRIMARY KEY(payment_option_code)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bonus (bonus_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, bonus_subject VARCHAR(64) NOT NULL, bonus_start_at DATETIME NOT NULL, bonus_end_at DATETIME NOT NULL, bonus_is_active TINYINT(1) DEFAULT \'1\' NOT NULL, bonus_terms LONGTEXT NOT NULL, bonus_image JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', bonus_deleted_at DATETIME DEFAULT NULL, bonus_created_by BIGINT UNSIGNED NOT NULL, bonus_created_at DATETIME NOT NULL, bonus_updated_by BIGINT UNSIGNED DEFAULT NULL, bonus_updated_at DATETIME DEFAULT NULL, PRIMARY KEY(bonus_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_refresh_token (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, user_id BIGINT UNSIGNED DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_4DD907325F37A13B (token), INDEX IDX_4DD9073219EB6921 (client_id), INDEX IDX_4DD90732A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (user_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, user_created_by BIGINT UNSIGNED DEFAULT NULL, user_group_id INT UNSIGNED DEFAULT NULL, user_username VARCHAR(255) NOT NULL, user_password VARCHAR(255) NOT NULL, user_email VARCHAR(255) NOT NULL, user_type INT DEFAULT 1 NOT NULL, user_is_active TINYINT(1) DEFAULT \'1\' NOT NULL, user_roles JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', user_deleted_at DATETIME DEFAULT NULL, user_zendesk_id BIGINT DEFAULT NULL, user_key VARCHAR(255) DEFAULT NULL, user_activation_code VARCHAR(150) DEFAULT NULL, user_activation_sent_timestamp DATETIME DEFAULT NULL, user_activation_timestamp DATETIME DEFAULT NULL, user_created_at DATETIME NOT NULL, user_updated_by BIGINT UNSIGNED DEFAULT NULL, user_updated_at DATETIME DEFAULT NULL, user_preferences JSON NOT NULL COMMENT \'(DC2Type:json)\', user_reset_password_code VARCHAR(150) DEFAULT NULL, user_reset_password_sent_timestamp DATETIME DEFAULT NULL, INDEX IDX_8D93D6491ED93D47 (user_group_id), INDEX IDX_8D93D64979756DBA (user_created_by), UNIQUE INDEX username_unq (user_username, user_type), UNIQUE INDEX email_unq (user_email, user_type), PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer_group_gateway (cgg_gateway_id INT UNSIGNED NOT NULL, cgg_customer_group_id INT UNSIGNED NOT NULL, cgg_conditions LONGTEXT NOT NULL, cgg_created_by BIGINT UNSIGNED NOT NULL, cgg_created_at DATETIME NOT NULL, cgg_updated_by BIGINT UNSIGNED DEFAULT NULL, cgg_updated_at DATETIME DEFAULT NULL, INDEX IDX_F69BF97051FE4520 (cgg_gateway_id), INDEX IDX_F69BF970CFEE8B7D (cgg_customer_group_id), PRIMARY KEY(cgg_gateway_id, cgg_customer_group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE session (id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, session_user_id BIGINT UNSIGNED NOT NULL, session_id VARCHAR(255) NOT NULL, session_key VARCHAR(255) NOT NULL, session_created_at DATETIME NOT NULL, INDEX IDX_D044D5D4B5B651CF (session_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE setting (setting_id INT UNSIGNED AUTO_INCREMENT NOT NULL, setting_code VARCHAR(64) NOT NULL, setting_value JSON NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_9F74B898B6A11C7E (setting_code), PRIMARY KEY(setting_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_auth_code (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, user_id BIGINT UNSIGNED DEFAULT NULL, token VARCHAR(255) NOT NULL, redirect_uri LONGTEXT NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_1D2905B55F37A13B (token), INDEX IDX_1D2905B519EB6921 (client_id), INDEX IDX_1D2905B5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (product_id INT UNSIGNED AUTO_INCREMENT NOT NULL, product_code VARCHAR(10) NOT NULL, product_name VARCHAR(64) NOT NULL, product_is_active TINYINT(1) DEFAULT \'1\' NOT NULL, product_logo_uri VARCHAR(255) DEFAULT NULL, product_url VARCHAR(255) DEFAULT NULL, product_created_by BIGINT UNSIGNED NOT NULL, product_created_at DATETIME NOT NULL, product_updated_by BIGINT UNSIGNED DEFAULT NULL, product_updated_at DATETIME DEFAULT NULL, product_details JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX UNIQ_D34A04ADFAFD1239 (product_code), UNIQUE INDEX UNIQ_D34A04ADD3CB5CA7 (product_name), PRIMARY KEY(product_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_group (group_id INT UNSIGNED AUTO_INCREMENT NOT NULL, group_name VARCHAR(64) NOT NULL, group_roles JSON NOT NULL COMMENT \'(DC2Type:json)\', group_created_by BIGINT UNSIGNED NOT NULL, group_created_at DATETIME NOT NULL, group_updated_by BIGINT UNSIGNED DEFAULT NULL, group_updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8F02BF9D77792576 (group_name), PRIMARY KEY(group_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE currency (currency_id INT UNSIGNED AUTO_INCREMENT NOT NULL, currency_code VARCHAR(5) NOT NULL, currency_name VARCHAR(250) NOT NULL, currency_rate NUMERIC(65, 10) DEFAULT \'1\' NOT NULL, currency_created_by BIGINT UNSIGNED NOT NULL, currency_created_at DATETIME NOT NULL, currency_updated_by BIGINT UNSIGNED DEFAULT NULL, currency_updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_6956883FFDA273EC (currency_code), UNIQUE INDEX UNIQ_6956883FD4943D72 (currency_name), PRIMARY KEY(currency_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE announcement (announcement_id INT UNSIGNED AUTO_INCREMENT NOT NULL, announcement_title VARCHAR(64) NOT NULL, announcement_description LONGTEXT NOT NULL, announcement_type SMALLINT UNSIGNED NOT NULL, announcement_start_at DATETIME NOT NULL, announcement_end_at DATETIME NOT NULL, announcement_is_active TINYINT(1) DEFAULT \'1\' NOT NULL, announcement_created_by BIGINT UNSIGNED NOT NULL, announcement_created_at DATETIME NOT NULL, announcement_updated_by BIGINT UNSIGNED DEFAULT NULL, announcement_updated_at DATETIME DEFAULT NULL, announcement_image_uri VARCHAR(255) DEFAULT NULL, PRIMARY KEY(announcement_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE dwl (dwl_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, dwl_product_id INT UNSIGNED NOT NULL, dwl_currency_id INT UNSIGNED NOT NULL, dwl_status SMALLINT UNSIGNED DEFAULT 1 NOT NULL, dwl_version SMALLINT UNSIGNED DEFAULT 1 NOT NULL, dwl_date DATE NOT NULL, dwl_details JSON NOT NULL COMMENT \'(DC2Type:json)\', dwl_created_by BIGINT UNSIGNED NOT NULL, dwl_created_at DATETIME NOT NULL, dwl_updated_by INT UNSIGNED DEFAULT NULL, dwl_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX IDX_94E578ACE35D056E (dwl_product_id), INDEX IDX_94E578AC1936ACA0 (dwl_currency_id), PRIMARY KEY(dwl_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gateway (gateway_id INT UNSIGNED AUTO_INCREMENT NOT NULL, gateway_payment_option VARCHAR(255) DEFAULT NULL, gateway_currency_id INT UNSIGNED NOT NULL, gateway_name VARCHAR(64) NOT NULL, gateway_balance NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, gateway_is_active TINYINT(1) DEFAULT \'1\' NOT NULL, gateway_created_by BIGINT UNSIGNED NOT NULL, gateway_created_at DATETIME NOT NULL, gateway_updated_by BIGINT UNSIGNED DEFAULT NULL, gateway_updated_at DATETIME DEFAULT NULL, gateway_details JSON NOT NULL COMMENT \'(DC2Type:json)\', gateway_levels JSON NOT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_14FEDD7FDF7081C (gateway_payment_option), INDEX IDX_14FEDD7FB2527E6 (gateway_currency_id), INDEX UNIQ_20170719002312 (gateway_name, gateway_currency_id), PRIMARY KEY(gateway_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gateway_log (gateway_log_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, gateway_log_gateway_id INT UNSIGNED DEFAULT NULL, gateway_log_currency_id INT UNSIGNED DEFAULT NULL, gateway_log_payment_option_code VARCHAR(16) DEFAULT NULL, gateway_log_timestamp DATETIME NOT NULL, gateway_log_type SMALLINT NOT NULL, gateway_log_balance NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, gateway_log_amount NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, gateway_log_reference_number VARCHAR(255) NOT NULL, gateway_log_details JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_E4BC7FD82F20B181 (gateway_log_gateway_id), INDEX IDX_E4BC7FD8A2E36DFF (gateway_log_currency_id), INDEX IDX_E4BC7FD876B8098E (gateway_log_payment_option_code), PRIMARY KEY(gateway_log_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sub_transaction (subtransaction_id BIGINT UNSIGNED AUTO_INCREMENT NOT NULL, subtransaction_transaction_id BIGINT UNSIGNED NOT NULL, subtransaction_customer_product_id BIGINT UNSIGNED DEFAULT NULL, subtransaction_type SMALLINT UNSIGNED NOT NULL, subtransaction_amount NUMERIC(65, 10) DEFAULT \'0\' NOT NULL, subtransaction_fees JSON NOT NULL COMMENT \'(DC2Type:json)\', subtransaction_details JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_97CE704F1F028B15 (subtransaction_transaction_id), INDEX IDX_97CE704F4464C11B (subtransaction_customer_product_id), PRIMARY KEY(subtransaction_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oauth2_access_token (id INT AUTO_INCREMENT NOT NULL, client_id INT DEFAULT NULL, user_id BIGINT UNSIGNED DEFAULT NULL, token VARCHAR(255) NOT NULL, expires_at INT DEFAULT NULL, scope VARCHAR(255) DEFAULT NULL, ip_address VARCHAR(255) DEFAULT \'\' NOT NULL, UNIQUE INDEX UNIQ_454D96735F37A13B (token), INDEX IDX_454D967319EB6921 (client_id), INDEX IDX_454D9673A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE gateway_transaction ADD CONSTRAINT FK_4136A341D98172AE FOREIGN KEY (gateway_transaction_gateway_id) REFERENCES gateway (gateway_id)');
        $this->addSql('ALTER TABLE gateway_transaction ADD CONSTRAINT FK_4136A3417D60C973 FOREIGN KEY (gateway_transaction_gateway_to_id) REFERENCES gateway (gateway_id)');
        $this->addSql('ALTER TABLE gateway_transaction ADD CONSTRAINT FK_4136A3419C4F165 FOREIGN KEY (gateway_transaction_currency_id) REFERENCES currency (currency_id)');
        $this->addSql('ALTER TABLE gateway_transaction ADD CONSTRAINT FK_4136A341FDCE26F6 FOREIGN KEY (gateway_transaction_payment_option_code) REFERENCES payment_option (payment_option_code)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D12F1CD6DE FOREIGN KEY (transaction_customer_id) REFERENCES customer (customer_id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1B8E9B9B1 FOREIGN KEY (transaction_gateway_id) REFERENCES gateway (gateway_id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D184AD945B FOREIGN KEY (transaction_currency_id) REFERENCES currency (currency_id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1ABCAC298 FOREIGN KEY (transaction_payment_option_id) REFERENCES customer_payment_option (customer_payment_option_id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1294149FB FOREIGN KEY (transaction_payment_option_type) REFERENCES payment_option (payment_option_code)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D131849CE7 FOREIGN KEY (transaction_created_by) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE country ADD CONSTRAINT FK_5373C9662DB3ABBD FOREIGN KEY (country_currency_id) REFERENCES currency (currency_id)');
        $this->addSql('ALTER TABLE customer_product ADD CONSTRAINT FK_CF97A013A32A23C6 FOREIGN KEY (cproduct_product_id) REFERENCES product (product_id)');
        $this->addSql('ALTER TABLE customer_product ADD CONSTRAINT FK_CF97A0136ACAB2D9 FOREIGN KEY (cproduct_customer_id) REFERENCES customer (customer_id)');
        $this->addSql('ALTER TABLE customer_payment_option ADD CONSTRAINT FK_E6D3AFECAE3CDBBC FOREIGN KEY (customer_payment_option_type) REFERENCES payment_option (payment_option_code)');
        $this->addSql('ALTER TABLE customer_payment_option ADD CONSTRAINT FK_E6D3AFEC2CB77FFD FOREIGN KEY (customer_payment_options_customer_id) REFERENCES customer (customer_id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09BBB3772B FOREIGN KEY (customer_user_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E093B6FAA7E FOREIGN KEY (customer_currency_id) REFERENCES currency (currency_id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E094E63AF2 FOREIGN KEY (customer_country_id) REFERENCES country (country_id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E0991CA0783 FOREIGN KEY (customer_affiliate_id) REFERENCES customer (customer_id)');
        $this->addSql('ALTER TABLE customer_groups ADD CONSTRAINT FK_41A8E521B151EDEB FOREIGN KEY (ccg_customer_id) REFERENCES customer (customer_id)');
        $this->addSql('ALTER TABLE customer_groups ADD CONSTRAINT FK_41A8E52181036024 FOREIGN KEY (ccg_customer_group_id) REFERENCES customer_group (customer_group_id)');
        $this->addSql('ALTER TABLE oauth2_refresh_token ADD CONSTRAINT FK_4DD9073219EB6921 FOREIGN KEY (client_id) REFERENCES oauth2_client (id)');
        $this->addSql('ALTER TABLE oauth2_refresh_token ADD CONSTRAINT FK_4DD90732A76ED395 FOREIGN KEY (user_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6491ED93D47 FOREIGN KEY (user_group_id) REFERENCES user_group (group_id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64979756DBA FOREIGN KEY (user_created_by) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE customer_group_gateway ADD CONSTRAINT FK_F69BF97051FE4520 FOREIGN KEY (cgg_gateway_id) REFERENCES gateway (gateway_id)');
        $this->addSql('ALTER TABLE customer_group_gateway ADD CONSTRAINT FK_F69BF970CFEE8B7D FOREIGN KEY (cgg_customer_group_id) REFERENCES customer_group (customer_group_id)');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4B5B651CF FOREIGN KEY (session_user_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE oauth2_auth_code ADD CONSTRAINT FK_1D2905B519EB6921 FOREIGN KEY (client_id) REFERENCES oauth2_client (id)');
        $this->addSql('ALTER TABLE oauth2_auth_code ADD CONSTRAINT FK_1D2905B5A76ED395 FOREIGN KEY (user_id) REFERENCES user (user_id)');
        $this->addSql('ALTER TABLE dwl ADD CONSTRAINT FK_94E578ACE35D056E FOREIGN KEY (dwl_product_id) REFERENCES product (product_id)');
        $this->addSql('ALTER TABLE dwl ADD CONSTRAINT FK_94E578AC1936ACA0 FOREIGN KEY (dwl_currency_id) REFERENCES currency (currency_id)');
        $this->addSql('ALTER TABLE gateway ADD CONSTRAINT FK_14FEDD7FDF7081C FOREIGN KEY (gateway_payment_option) REFERENCES payment_option (payment_option_code)');
        $this->addSql('ALTER TABLE gateway ADD CONSTRAINT FK_14FEDD7FB2527E6 FOREIGN KEY (gateway_currency_id) REFERENCES currency (currency_id)');
        $this->addSql('ALTER TABLE gateway_log ADD CONSTRAINT FK_E4BC7FD82F20B181 FOREIGN KEY (gateway_log_gateway_id) REFERENCES gateway (gateway_id)');
        $this->addSql('ALTER TABLE gateway_log ADD CONSTRAINT FK_E4BC7FD8A2E36DFF FOREIGN KEY (gateway_log_currency_id) REFERENCES currency (currency_id)');
        $this->addSql('ALTER TABLE gateway_log ADD CONSTRAINT FK_E4BC7FD876B8098E FOREIGN KEY (gateway_log_payment_option_code) REFERENCES payment_option (payment_option_code)');
        $this->addSql('ALTER TABLE sub_transaction ADD CONSTRAINT FK_97CE704F1F028B15 FOREIGN KEY (subtransaction_transaction_id) REFERENCES transaction (transaction_id)');
        $this->addSql('ALTER TABLE sub_transaction ADD CONSTRAINT FK_97CE704F4464C11B FOREIGN KEY (subtransaction_customer_product_id) REFERENCES customer_product (cproduct_id)');
        $this->addSql('ALTER TABLE oauth2_access_token ADD CONSTRAINT FK_454D967319EB6921 FOREIGN KEY (client_id) REFERENCES oauth2_client (id)');
        $this->addSql('ALTER TABLE oauth2_access_token ADD CONSTRAINT FK_454D9673A76ED395 FOREIGN KEY (user_id) REFERENCES user (user_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE sub_transaction DROP FOREIGN KEY FK_97CE704F1F028B15');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E094E63AF2');
        $this->addSql('ALTER TABLE sub_transaction DROP FOREIGN KEY FK_97CE704F4464C11B');
        $this->addSql('ALTER TABLE customer_groups DROP FOREIGN KEY FK_41A8E52181036024');
        $this->addSql('ALTER TABLE customer_group_gateway DROP FOREIGN KEY FK_F69BF970CFEE8B7D');
        $this->addSql('ALTER TABLE oauth2_refresh_token DROP FOREIGN KEY FK_4DD9073219EB6921');
        $this->addSql('ALTER TABLE oauth2_auth_code DROP FOREIGN KEY FK_1D2905B519EB6921');
        $this->addSql('ALTER TABLE oauth2_access_token DROP FOREIGN KEY FK_454D967319EB6921');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1ABCAC298');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D12F1CD6DE');
        $this->addSql('ALTER TABLE customer_product DROP FOREIGN KEY FK_CF97A0136ACAB2D9');
        $this->addSql('ALTER TABLE customer_payment_option DROP FOREIGN KEY FK_E6D3AFEC2CB77FFD');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E0991CA0783');
        $this->addSql('ALTER TABLE customer_groups DROP FOREIGN KEY FK_41A8E521B151EDEB');
        $this->addSql('ALTER TABLE gateway_transaction DROP FOREIGN KEY FK_4136A341FDCE26F6');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1294149FB');
        $this->addSql('ALTER TABLE customer_payment_option DROP FOREIGN KEY FK_E6D3AFECAE3CDBBC');
        $this->addSql('ALTER TABLE gateway DROP FOREIGN KEY FK_14FEDD7FDF7081C');
        $this->addSql('ALTER TABLE gateway_log DROP FOREIGN KEY FK_E4BC7FD876B8098E');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D131849CE7');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09BBB3772B');
        $this->addSql('ALTER TABLE oauth2_refresh_token DROP FOREIGN KEY FK_4DD90732A76ED395');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64979756DBA');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4B5B651CF');
        $this->addSql('ALTER TABLE oauth2_auth_code DROP FOREIGN KEY FK_1D2905B5A76ED395');
        $this->addSql('ALTER TABLE oauth2_access_token DROP FOREIGN KEY FK_454D9673A76ED395');
        $this->addSql('ALTER TABLE customer_product DROP FOREIGN KEY FK_CF97A013A32A23C6');
        $this->addSql('ALTER TABLE dwl DROP FOREIGN KEY FK_94E578ACE35D056E');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6491ED93D47');
        $this->addSql('ALTER TABLE gateway_transaction DROP FOREIGN KEY FK_4136A3419C4F165');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D184AD945B');
        $this->addSql('ALTER TABLE country DROP FOREIGN KEY FK_5373C9662DB3ABBD');
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E093B6FAA7E');
        $this->addSql('ALTER TABLE dwl DROP FOREIGN KEY FK_94E578AC1936ACA0');
        $this->addSql('ALTER TABLE gateway DROP FOREIGN KEY FK_14FEDD7FB2527E6');
        $this->addSql('ALTER TABLE gateway_log DROP FOREIGN KEY FK_E4BC7FD8A2E36DFF');
        $this->addSql('ALTER TABLE gateway_transaction DROP FOREIGN KEY FK_4136A341D98172AE');
        $this->addSql('ALTER TABLE gateway_transaction DROP FOREIGN KEY FK_4136A3417D60C973');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1B8E9B9B1');
        $this->addSql('ALTER TABLE customer_group_gateway DROP FOREIGN KEY FK_F69BF97051FE4520');
        $this->addSql('ALTER TABLE gateway_log DROP FOREIGN KEY FK_E4BC7FD82F20B181');
        $this->addSql('DROP TABLE gateway_transaction');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP TABLE customer_product');
        $this->addSql('DROP TABLE customer_group');
        $this->addSql('DROP TABLE oauth2_client');
        $this->addSql('DROP TABLE customer_payment_option');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE customer_groups');
        $this->addSql('DROP TABLE payment_option');
        $this->addSql('DROP TABLE bonus');
        $this->addSql('DROP TABLE oauth2_refresh_token');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE customer_group_gateway');
        $this->addSql('DROP TABLE session');
        $this->addSql('DROP TABLE setting');
        $this->addSql('DROP TABLE oauth2_auth_code');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE user_group');
        $this->addSql('DROP TABLE currency');
        $this->addSql('DROP TABLE announcement');
        $this->addSql('DROP TABLE dwl');
        $this->addSql('DROP TABLE gateway');
        $this->addSql('DROP TABLE gateway_log');
        $this->addSql('DROP TABLE sub_transaction');
        $this->addSql('DROP TABLE oauth2_access_token');
    }
}
