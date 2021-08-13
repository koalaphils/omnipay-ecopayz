<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210719063234 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
	    $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

	    /*
	    // Transaction
	    $this->addSql('UPDATE transaction set transaction_other_details = JSON_SET(transaction_other_details, \'$.paymentOptionOnTransaction.code\', COALESCE(transaction_payment_option_type, (transaction_other_details->>\'$.paymentOption.code\'), (SELECT customer_payment_option_type from customer_payment_option where customer_payment_option_id = transaction_payment_option_on_transaction_id), (select customer_payment_option_type from customer_payment_option where customer_payment_option_id = transaction_payment_option_id)))');
	    $this->addSql('ALTER TABLE transaction DROP CONSTRAINT FK_723705D1294149FB, DROP COLUMN transaction_payment_option_type');
	    $this->addSql('ALTER TABLE transaction ADD transaction_payment_option_type VARCHAR(255) GENERATED ALWAYS AS (transaction_other_details->>"$.paymentOptionOnTransaction.code") NULL');
	    $this->addSql('CREATE INDEX `transaction_payment_option_type_idx` ON `transaction`(`transaction_payment_option_type`)');

	    // Country
	    $this->addSql('ALTER TABLE customer ADD customer_country_code VARCHAR(4) AFTER customer_country_id');
	    $this->addSql('UPDATE customer SET customer_country_code = (SELECT country_code FROM country WHERE country_id=customer_country_id)');
	    $this->addSql('UPDATE customer SET customer_country_code = NULL WHERE customer_country_code = \'UNK\'');
	    $this->addSql('UPDATE customer SET customer_country_code = \'GB\' WHERE customer_country_code=\'UKE\'');
	    $this->addSql('ALTER TABLE customer DROP CONSTRAINT FK_81398E094E63AF2');
	    $this->addSql('ALTER TABLE customer DROP KEY IDX_81398E094E63AF2');
	    $this->addSql('ALTER TABLE customer DROP customer_country_id');
	    $this->addSql('UPDATE customer SET customer_country_code = \'MK\' WHERE customer_country_code=\'YU\'');
	    $this->addSql('UPDATE customer SET customer_country_code = \'NL\' WHERE customer_country_code=\'AN\'');
	    $this->addSql('UPDATE customer SET customer_country_code = \'FR\' WHERE customer_country_code=\'FRM\'');
	    */
	    // Gateway
	    $this->addSql('ALTER TABLE gateway DROP FOREIGN KEY FK_14FEDD7FDF7081C');

	    /*
	    $gatewayFees = [
		    //ECOPAYZ
		    ['paymentOption' => 'ecopayz', 'gatewayType' => 'merchant', 'transactionType' => 'deposit', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    ['paymentOption' => 'ecopayz', 'gatewayType' => 'personal', 'transactionType' => 'deposit', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    ['paymentOption' => 'ecopayz', 'gatewayType' => 'merchant', 'transactionType' => 'withdraw', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    ['paymentOption' => 'ecopayz', 'gatewayType' => 'personal', 'transactionType' => 'withdraw', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    //USDT
		    ['paymentOption' => 'usdt', 'gatewayType' => 'merchant', 'transactionType' => 'deposit', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    ['paymentOption' => 'usdt', 'gatewayType' => 'personal', 'transactionType' => 'deposit', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    ['paymentOption' => 'usdt', 'gatewayType' => 'merchant', 'transactionType' => 'withdraw', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    ['paymentOption' => 'usdt', 'gatewayType' => 'personal', 'transactionType' => 'withdraw', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    //BITCOIN
		    ['paymentOption' => 'bitcoin', 'gatewayType' => 'merchant', 'transactionType' => 'deposit', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    ['paymentOption' => 'bitcoin', 'gatewayType' => 'personal', 'transactionType' => 'deposit', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    ['paymentOption' => 'bitcoin', 'gatewayType' => 'merchant', 'transactionType' => 'withdraw', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
		    ['paymentOption' => 'bitcoin', 'gatewayType' => 'personal', 'transactionType' => 'withdraw', 'rates' => [
			    'first' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
			    'succeeding' => ['memberFeePercentage' => 0, 'companyFeePercentage' => 0],
		    ]],
	    ];

	    $this->addSql('
            UPDATE setting 
            SET setting_value = JSON_SET(setting_value, "$.fees", JSON_MERGE_PRESERVE(JSON_EXTRACT(setting_value, "$.fees"), ?)) 
            WHERE setting_code = ?',
		    [json_encode($gatewayFees), 'gateway.fees']
	    );
	    */
    }

    public function down(Schema $schema) : void
    {
	    $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

	    // Transaction
	    $this->addSql('ALTER TABLE transaction DROP COLUMN transaction_payment_option_type');
	    $this->addSql('ALTER TABLE transaction ADD transaction_payment_option_type VARCHAR(255) NULL');
	    $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1294149FB FOREIGN KEY (transaction_payment_option_type) REFERENCES payment_option (payment_option_code)');
	    $this->addSql('UPDATE transaction SET transaction_payment_option_type=JSON_UNQUOTE(JSON_EXTRACT(transaction_other_details, \'$.paymentOptionOnTransaction.code\'))');

	    // Country
	    $this->addSql('ALTER TABLE customer ADD customer_country_id UNSIGNED INT');
	    $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E094E63AF2 FOREIGN KEY (customer_country_id) REFERENCES country(country_id)');
	    $this->addSql('ALTER TABLE customer ADD INDEX IDX_81398E094E63AF2(customer_country_id)');
	    $this->addSql('UPDATE customer SET customer_country_id=(SELECT country_id FROM country where country_code=customer_country_code)');
	    $this->addSql('UPDATE customer SET customer_country_code = \'YU\' WHERE customer_country_code=\'MK\'');
	    $this->addSql('UPDATE customer SET customer_country_code = \'AN\' WHERE customer_country_code=\'NL\'');
	    $this->addSql('UPDATE customer SET customer_country_code = \'FRM\' WHERE customer_country_code=\'FR\'');
	    $this->addSql('ALTER TABLE customer DROP customer_country_code');

	    // Gateway
	    $this->addSql('ALTER TABLE gateway CHANGE gateway_type gateway_type VARCHAR(25) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`');
	    $this->addSql('ALTER TABLE gateway ADD CONSTRAINT FK_14FEDD7FDF7081C FOREIGN KEY (gateway_payment_option) REFERENCES payment_option (payment_option_code)');
    }
}
