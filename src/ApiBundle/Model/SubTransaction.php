<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ApiBundle\Model;

/**
 * Description of SubTransaction
 *
 * @author cnonog
 */
class SubTransaction
{
    private $product;
    private $amount;
    private $forFee;
    private $type;
    private $transaction;
    private $username;
    private $code;
    private $paymentDetails;

    public function getProduct()
    {
        return $this->product;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getForFee()
    {
        return $this->forFee;
    }

    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    public function setForFee($forFee)
    {
        $this->forFee = $forFee;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
    
    public function getTransaction()
    {
        return $this->transaction;
    }
    
    public function setTransaction($transaction)
    {
        $this->transaction = $transaction;
        
        return $this;
    }
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function getCode()
    {
        return $this->code;
    }
    
    public function setUsername($username)
    {
        $this->username = $username;
        
        return $this;
    }
    
    public function setCode($code)
    {
        $this->code = $code;
        
        return $this;
    }
    
    public function getPaymentDetails(): ?PaymentInterface
    {
        return $this->paymentDetails;
    }
    
    public function setPaymentDetails(?PaymentInterface $paymentDetails): self
    {
        $this->paymentDetails = $paymentDetails;
        if ($this->paymentDetails instanceof PaymentInterface && $this->getTransaction() instanceof Transaction) {
            $this->paymentDetails->setTransaction($this->getTransaction());
        }
        
        return $this;
    }
    
    public function hasPaymentDetails(): bool
    {
        return $this->getPaymentDetails() instanceof PaymentInterface;
    }

    public function isPaymentBitcoin(): bool
    {
        if (!empty($this->getPaymentDetails()) && isset($this->getPaymentDetails()->getDetails()['bitcoin'])) {
            return true;
        }

        return false;
    }
}
