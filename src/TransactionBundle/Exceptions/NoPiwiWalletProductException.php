<?php

namespace TransactionBundle\Exceptions;

class NoPiwiWalletProductException extends \Exception
{
    public function __construct($customer)
    {
        parent::__construct('A customer with ID of '.  $customer->getId() . 'has no PIWI Wallet Product. Please create one.');
    }
}