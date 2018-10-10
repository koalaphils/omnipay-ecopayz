<?php

namespace Helper;

use Codeception\Module;
use DateTime;
use DbBundle\Entity\Currency;
use DbBundle\Entity\CurrencyRate;
use DbBundle\Entity\Customer as Member;
use DbBundle\Entity\CustomerProduct as MemberProduct;
use DbBundle\Entity\DWL;
use DbBundle\Entity\Product;
use DbBundle\Entity\SubTransaction;
use DbBundle\Entity\Transaction;
use DbBundle\Entity\User;
use League\FactoryMuffin\Faker\Facade as Faker;


// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Factories extends Module
{
    public function _beforeSuite($settings = [])
    {
        $factory = $this->getModule('DataFactory');

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
            'name' => Faker::unique()->text(15)
        ]);

        $factory->_define(Member::class, [
            'user' => 'entity|' . User::class,
            'fullName' => Faker::name(),
            'joinedAt' => new DateTime(),
            'currency' => 'entity|' . Currency::class,
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
            'customer' => 'entity|' . Member::class,
            'currency' => 'entity|' . Currency::class,
        ]);
        $factory->_define(CurrencyRate::class, [
            'sourceCurrency' => 'entity|' . Currency::class,
            'destinationCurrency' => 'entity|' . Currency::class,
            'destinationRate' => 1,
            'rate' => Faker::decimal(2),
        ]);
    }

    private function getUniqueCurrencyCode()
    {
        $code = Faker::unique()->currencyCode();
        $existingCurrencies = ['EUR','GBP','BTC'];
        if (in_array($code, $existingCurrencies)) {
            return $this->getUniqueCurrencyCode();
        }
        return $code;
    }
}
