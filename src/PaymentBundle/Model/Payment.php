<?php

namespace PaymentBundle\Model;

use DbBundle\Entity\Transaction;

class Payment extends \Payum\Core\Model\ArrayObject
{
    public function setTransaction(Transaction $transaction): void
    {
        $this->details['transaction'] = $transaction;
    }
    
    public function getTransaction(): ?Transaction
    {
        return $this->details['transaction'];
    }
    
    public function setGateway(\DbBundle\Entity\Gateway $gateway): void
    {
        $this->details['gateway'] = $gateway;
    }
    
    public function getGateway(): ?\DbBundle\Entity\Gateway
    {
        return $this->details['gateway'];
    }
}
