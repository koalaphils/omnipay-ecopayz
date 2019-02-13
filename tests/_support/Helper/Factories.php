<?php

namespace Helper;

use Codeception\Module;
use DateTime;
use DbBundle\Entity\AuditRevision;
use DbBundle\Entity\AuditRevisionLog;
use DbBundle\Entity\Country;
use DbBundle\Entity\Currency;
use DbBundle\Entity\CurrencyRate;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\DWL;
use DbBundle\Entity\Gateway;
use DbBundle\Entity\PaymentOption;
use DbBundle\Entity\MemberReferralName;
use DbBundle\Entity\Product;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\User;
use League\FactoryMuffin\Faker\Facade as Faker;


// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Factories extends Module
{
    private static $currencyCollections = [];
    private static $countryCollections = [];

    public function _beforeSuite($settings = [])
    {
        $factory = $this->getModule('DataFactory');
        $em = $this->getModule('Doctrine2')->_getEntityManager();

        $factory->_define(Country::class, [
            'name' => $this->getUniqueCountryCode(),
            'code' => $this->getUniqueCountryCode(),
            'tags' => null,
        ]);

        $factory->_define(User::class, [
            'username' => Faker::username(),
            'password' => 'this_is_from_a_test',
            'email' => Faker::email(),
            'type' => 1,
            'isActive' => 1,
            'createdAt' => new DateTime(),
            'preferences' => '{}'
        ]);

        $factory->_define(MemberProduct::class, [
            'customer' => 'entity|' . Member::class,
            'username' => Faker::username(),
            'product' => 'entity|' . Product::class,
            'isActive' => 1,
            'balance' => 000,
        ]);

        $factory->_define(Product::class, [
            'code' => Faker::unique()->text(5),
            'name' => Faker::unique()->text(15),
            'isActive' => 1,
        ]);

        $factory->_define(Currency::class, [
            'code' => $this->getUniqueCurrencyCode(),
            'name' => $this->getUniqueCurrencyCode(),
        ]);

        $factory->_define(Member::class, [
            'user' => 'entity|' . User::class,
            'fullName' => Faker::name(),
            'joinedAt' => Faker::dateTimeBetween('-30 years', 'now'),
            'birthDate' => Faker::dateTimeBetween('-80 years', 'now'),
            'currency' => 'entity|' . Currency::class,
            'country' => 'entity|' . Country::class,
        ]);

        $factory->_define(DWL::class, [
            'product' => 'entity|' . Product::class,
            'currency' => 'entity|' . Currency::class,
        ]);

        $factory->_define(SubTransaction::class, [
            'customerProduct' => 'entity|' . MemberProduct::class,
            'parent' => 'entity|' . Transaction::class,
        ]);

        $factory->_define(Transaction::class, [
            'type' => Faker::randomElement([
                Transaction::TRANSACTION_TYPE_BET,
                Transaction::TRANSACTION_TYPE_BONUS,
                Transaction::TRANSACTION_TYPE_COMMISSION,
                Transaction::TRANSACTION_TYPE_DEPOSIT,
                Transaction::TRANSACTION_TYPE_DWL,
                Transaction::TRANSACTION_TYPE_P2P_TRANSFER,
                Transaction::TRANSACTION_TYPE_TRANSFER,
                Transaction::TRANSACTION_TYPE_WITHDRAW,
            ]),
            'customer' => 'entity|' . Member::class,
            'currency' => 'entity|' . Currency::class,
            'number' => Faker::uuid(),
            'date' => Faker::dateTime(),
            'subTransactions' => [],
            'details' => [],
        ]);

        $factory->_define(CurrencyRate::class, [
            'sourceCurrency' => 'entity|' . Currency::class,
            'destinationCurrency' => 'entity|' . Currency::class,
            'destinationRate' => 1,
            'rate' => Faker::decimal(2),
        ]);
 
        $factory->_define(PaymentOption::class, [
            'code' => Faker::unique()->text(6),
            'name' => Faker::unique()->text(10),
            'fields' => [],
            'image' => '',
            'isActive' => true,
            'autoDecline' => false,
            'paymentMode' => Faker::randomElement([
                PaymentOption::PAYMENT_MODE_BITCOIN,
                PaymentOption::PAYMENT_MODE_ECOPAYZ,
                PaymentOption::PAYMENT_MODE_OFFLINE,
            ]),
        ]);
        
        $factory->_define(Gateway::class, [
            'name' => Faker::unique()->text(6),
            'currency' => 'entity|' . Currency::class,
            'balance' => 0,
            'isActive' => true,
            'paymentOptionEntity' => 'entity|' . PaymentOption::class,
            'details' => [], 
        ]);

        $factory->_define(AuditRevision::class, []);

        $factory->_define(AuditRevisionLog::class, [
            'details' => [],
            'auditRevision' => 'entity|' . AuditRevision::class,
        ]);

        $factory->_define(MemberReferralName::class, [
            'name' => Faker::unique()->randomDigit(),
            'isActive' => 1,
            'member' => 'entity|' . Member::class,
        ]);
    }

    private static function getUniqueCurrencyCode()
    {
        return function () {
            $acceptedCharacters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $acceptedCharactersAsArray = str_split($acceptedCharacters);

            $randomCharacters = array_rand($acceptedCharactersAsArray, 3);
            $code = $acceptedCharactersAsArray[$randomCharacters[0]] . $acceptedCharactersAsArray[$randomCharacters[1]] . $acceptedCharactersAsArray[$randomCharacters[2]];
            if (in_array($code, Factories::$currencyCollections)) {
                return (Factories::getUniqueCurrencyCode())();
            }
            Factories::$currencyCollections[] = $code;

            return $code;
        };
    }

    private static function getUniqueCountryCode()
    {
        return function () {
            $acceptedCharacters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $acceptedCharactersAsArray = str_split($acceptedCharacters);

            $randomCharacters = array_rand($acceptedCharactersAsArray, 5);
            $code = $acceptedCharactersAsArray[$randomCharacters[0]] . $acceptedCharactersAsArray[$randomCharacters[1]] . $acceptedCharactersAsArray[$randomCharacters[2]];
            if (in_array($code, Factories::$countryCollections)) {
                return (Factories::getUniqueCountryCode())();
            }
            Factories::$countryCollections[] = $code;

            return $code;
        };
    }
}
