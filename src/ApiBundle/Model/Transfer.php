<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ApiBundle\Model;

use Doctrine\Common\Collections\ArrayCollection;
use ApiBundle\Model\SubTransaction;
use DbBundle\Entity\Customer;
use DbBundle\Entity\CustomerProduct;
use AppBundle\ValueObject\Number;

/**
 * Description of Transfer
 *
 * @author cnonog
 */
class Transfer
{
    private $customer;
    private $to;
    private $from;
    private $transactionPassword;

    public function __construct()
    {
        $this->setTo(new ArrayCollection([]));
    }

    public function addTo(SubTransaction $subtransaction): self
    {
        $subtransaction->setTransaction($this);
        $this->getTo()->add($subtransaction);

        return $this;
    }

    public function getCustomer(): Customer
    {
        return $this->customer;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getTo(): ArrayCollection
    {
        return $this->to;
    }

    public function setCustomer(Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function setFrom(CustomerProduct $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function setTo($to): self
    {
        if ($to instanceof ArrayCollection) {
            $this->to = $to;
        } elseif (is_array($to)) {
            $this->to = new ArrayCollection($to);
            foreach ($to as $_to) {
                $this->to->add($_to->setTransaction($this));
            }
        } else {
            $this->to = new ArrayCollection([]);
            $this->to->add($to->setTransaction($this));
        }

        return $this;
    }

    public function getTotalAmount()
    {
        $amount = new Number(0);
        foreach ($this->getTo() as $to) {
            if ($to->getAmount()) {
                $amount = $amount->plus($to->getAmount());
            }
        }

        return $amount;
    }

    public function getToCustomer()
    {
        $customerTo = null;
        foreach ($this->getTo() as $to) {
            if ($customerTo === null) {
                $customerTo = $to->getProduct()->getCustomer()->getId();
            } elseif ($customerTo !== $to->getProduct()->getCustomer()->getId()) {
                if (!is_array($customerTo)) {
                    $customerTo = [$customerTo];
                }
                $customerTo[] = $to->getProduct()->getCustomer()->getId();
            }
        }

        return $customerTo;
    }

    public function isMultipleToCustomer(): bool
    {
        return is_array($this->getToCustomer());
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
}
