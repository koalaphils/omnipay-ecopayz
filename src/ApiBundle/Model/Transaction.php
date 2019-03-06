<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ApiBundle\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use DbBundle\Entity\Customer;
use DbBundle\Entity\Transaction as EntityTransaction;
use DbBundle\Entity\CustomerPaymentOption as MemberPaymentOption;
use DbBundle\Entity\PaymentOption as Payment;
use DbBundle\Entity\Product;

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
    #zimi
    private $amount;
    private $bitcoinRate;    
    private $product;
    private $customerBitcoinAddress;
    
    private $accountId;
    private $file;
    private $type;

    public function __construct(int $type =  EntityTransaction::TRANSACTION_TYPE_DEPOSIT)
    {
        $this->setSubTransactions([]);
        $this->type = $type;
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

    public function setProduct($product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
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

    public function setAccountId(string $accountId): self
    {
        $this->accountId = $accountId;

        return $this;
    }

    public function getAccountId(): ?string
    {
        return $this->accountId;
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

    // zimi
    /**
     * Set amount.
     *
     * @param string $amount
     *
     * @return Transaction
     */
    public function setAmount($amount = 0)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount.     
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set bitcoin rate
     *
     * @param string $bitcoinRate
     *
     * @return string
     */
    public function setBitcoinRate($rate = '')
    {
        $this->bitcoinRate = $rate;

        return $this;
    }

    /**
     * Get bitcoin Rate     
     *
     * @return string
     */
    public function getBitcoinRate()
    {
        return $this->bitcoinRate;
    }

    /**
     * Set Customer Bitcoin Address
     *
     * @param string $bitcoinRate
     *
     * @return string
     */
    public function setCustomerBitcoinAddress($address = '')
    {
        $this->customerBitcoinAddress = $address;

        return $this;
    }

    /**
     * Get Customer Bitcoin Address
     *
     * @return string
     */
    public function getCustomerBitcoinAddress()
    {
        return $this->customerBitcoinAddress;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(UploadedFile $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getType(): int {
        return $this->type;
    }
}

