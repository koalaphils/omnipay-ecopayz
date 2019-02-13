<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ApiBundle\Model;

use Symfony\Component\HttpFoundation\Request;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerPaymentOption as MemberPaymentOption;
use DbBundle\Entity\PaymentOption as Payment;

/**
 * Description of Transaction
 *
 * @author cnonog
 */
class Transaction
{
    private $subTransactions;
    private $customer;
    private $memberPaymentOption;
    private $payment;

    private $email;
    private $transactionPassword;
    private $customerFee;
    private $paymentDetails;
    private $bankDetails;

    public function __construct()
    {
        $this->setSubTransactions([]);
    }

    public function getSubTransactions(): \Doctrine\Common\Collections\ArrayCollection
    {
        return $this->subTransactions;
    }

    public function setSubTransactions($subTransactions): self
    {
        if ($subTransactions instanceof \Doctrine\Common\Collections\ArrayCollection) {
            $this->subTransactions = $subTransactions;
        } else {
            $this->subTransactions = new \Doctrine\Common\Collections\ArrayCollection($subTransactions);
        }

        return $this;
    }

    public function addSubTransaction($subTransaction): self
    {
        $this->subTransactions->add($subTransaction);

        return $this;
    }

    public function setCustomer($customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setPaymentOption($memberPaymentOption): self
    {
        $this->memberPaymentOption = $memberPaymentOption;

        return $this;
    }

    public function getPaymentOption(): MemberPaymentOption
    {
        return $this->memberPaymentOption;
    }
    
    public function setPayment($payment): self
    {
        $this->payment = $payment;

        return $this;
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setTransactionPassword(string $transactionPassword): self
    {
        $this->transactionPassword = $transactionPassword;

        return $this;
    }

    public function getTransactionPassword(): ?string
    {
        return $this->transactionPassword;
    }

    public function setCustomerFee($customerFee = 0): self
    {
        $this->customerFee = $customerFee;

        return $this;
    }

    public function getCustomerFee()
    {
        return $this->customerFee;
    }
    
    public function getPaymentDetails(): ?PaymentInterface
    {
        return $this->paymentDetails;
    }
    
    public function setPaymentDetails(?PaymentInterface $paymentDetails): self
    {
        $this->paymentDetails = $paymentDetails;
        if ($paymentDetails instanceof PaymentInterface) {
            $this->paymentDetails->setTransaction($this);
        }
        
        return $this;
    }
    
    public function hasPaymentDetails(): bool
    {
        return $this->getPaymentDetails() instanceof PaymentInterface;
    }

    public function setBankDetails(string $bankDetails): self
    {
        $this->bankDetails = $bankDetails;

        return $this;
    }

    public function getBankDetails(): ?string
    {
        return $this->bankDetails;
    }
}
