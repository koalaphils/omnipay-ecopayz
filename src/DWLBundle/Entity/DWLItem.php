<?php

namespace DWLBundle\Entity;

use AppBundle\ValueObject\Number;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use DbBundle\Entity\DWL;

class DWLItem
{
    protected $dwl;
    protected $username;
    protected $customerProduct;
    protected $turnover;
    protected $gross;
    protected $winLoss;
    protected $commission;
    protected $amount;
    protected $updatedAt;
    protected $version;
    protected $transaction;
    protected $subTransaction;

    /**
     * Set daily win loss.
     *
     * @param \DbBundle\Entity\DWL $dwl
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setDwl($dwl)
    {
        $this->dwl = $dwl;

        return $this;
    }

    /**
     * Get dwl.
     *
     * @return \DbBundle\Entity\DWL
     */
    public function getDwl()
    {
        return $this->dwl;
    }

    /**
     * Set username.
     *
     * @param string $username
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get Username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set customer product.
     *
     * @param \DbBundle\Entity\CustomerProduct $customerProduct
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setCustomerProduct($customerProduct)
    {
        $this->customerProduct = $customerProduct;

        return $this;
    }

    /**
     * Get customer product.
     *
     * @return \DbBundle\Entity\CustomerProduct
     */
    public function getCustomerProduct()
    {
        return $this->customerProduct;
    }

    /**
     * Set turnover.
     *
     * @param number $turnover
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setTurnover($turnover)
    {
        $this->turnover = $turnover;

        return $this;
    }

    /**
     * Get turnover.
     *
     * @return number
     */
    public function getTurnover()
    {
        return $this->turnover;
    }

    /**
     * Set Gross commission.
     *
     * @param number $gross
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setGross($gross)
    {
        $this->gross = $gross;

        return $this;
    }

    /**
     * Get gross commission.
     *
     * @return number
     */
    public function getGross()
    {
        return $this->gross;
    }

    /**
     * Set member win loss.
     *
     * @param number $winLoss
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setWinLoss($winLoss)
    {
        $this->winLoss = $winLoss;

        return $this;
    }

    /**
     * Get member win loss.
     *
     * @return number
     */
    public function getWinLoss()
    {
        return $this->winLoss;
    }

    /**
     * Set member commission.
     *
     * @param number $commission
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setCommission($commission)
    {
        $this->commission = $commission;

        return $this;
    }

    /**
     * Get member commission.
     *
     * @return number
     */
    public function getCommission()
    {
        return $this->commission;
    }

    /**
     * Set total amount.
     *
     * @param number $amount
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get total amount.
     *
     * @return number
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set version.
     *
     * @param number $version
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return number
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set updated at.
     *
     * @param \DateTime $updatedAt
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updated at.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set transaction.
     *
     * @param \DbBundle\Entity\Transaction $transaction
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setTransaction($transaction)
    {
        $this->transaction = $transaction;

        return $this;
    }

    /**
     * Get transaction.
     *
     * @return \DbBundle\Entity\Transaction
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * Set sub transaction.
     *
     * @param \DbBundle\Entity\SubTransaction $subTransaction
     *
     * @return \DWLBundle\Entity\DWLItem
     */
    public function setSubTransaction($subTransaction)
    {
        $this->subTransaction = $subTransaction;

        return $this;
    }

    /**
     * Get sub transaction.
     *
     * @return \DbBundle\Entity\SubTransaction
     */
    public function getSubTransaction()
    {
        return $this->subTransaction;
    }

    public function getCalculatedAmount()
    {
        if (is_numeric($this->getCommission()) && is_numeric($this->getWinLoss())) {
            return Number::add($this->getCommission(), $this->getWinLoss())->toFloat();
        }

        return null;
    }

    public function validate(ExecutionContextInterface $context)
    {
        if (is_numeric($this->getCommission()) && is_numeric($this->getWinLoss())) {
            $calculatedAmount = $this->getCalculatedAmount();
            if ((new Number($this->getAmount()))->notEqual($calculatedAmount)) {
                $context->buildViolation('Incorrect Amount')->atPath('amount')->addViolation();
            }
        }
    }
}
