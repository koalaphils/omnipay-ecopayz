<?php

namespace DbBundle\Entity;

use DbBundle\Entity\Interfaces\AuditInterface;

/**
 * SubTransaction.
 */
class SubTransaction extends Entity implements AuditInterface
{
    /**
     * @var Transaction
     */
    private $parent;

    /**
     * @var tinyint
     */
    private $type;

    /**
     * @var string
     */
    private $amount = 0;

    /**
     * @var json
     */
    private $fees = [];

    /**
     * @var CustomerProduct
     */
    private $customerProduct;

    /**
     * @var json
     */
    private $details;

    private $dwlId;

    private $dwlTurnover;

    private $dwlWinLoss;

    public function __construct()
    {
        $this->fees = [];
        $this->setDetails([]);
    }

    /**
     * Set parent transaction.
     *
     * @param Transaction $parent
     *
     * @return SubTransaction
     */
    public function setParent(Transaction $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent transaction.
     *
     * @return Transaction
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set type.
     *
     * @param tinyint $type
     *
     * @return SubTransaction
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): int
    {
        return (int) $this->type;
    }

    public function getTypeText()
    {
        return $this->getParent()->getTypesText()[$this->getType()];
    }

    public function isWithdrawal() : bool
    {
        return $this->getType() == Transaction::TRANSACTION_TYPE_WITHDRAW;
    }

    public function isDeposit() : bool
    {
        return $this->getType() == Transaction::TRANSACTION_TYPE_DEPOSIT;
    }

    public function isDWL(): bool
    {
        return $this->getType() == Transaction::TRANSACTION_TYPE_DWL;
    }

    public function isBet() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_BET;
    }

    public function isVoided(): bool
    {
        return $this->getParent()->isVoided();
    }

    /**
     * Set amount.
     *
     * @param string $amount
     *
     * @return SubTransaction
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
     * Set fess.
     *
     * @param json $fees
     *
     * @return SubTransaction
     */
    public function setFees($fees = [])
    {
        $this->fees = $fees;

        return $this;
    }

    /**
     * Get fess.
     *
     * @return json
     */
    public function getFees()
    {
        return $this->fees;
    }

    public function getFee(string $name, $default = null)
    {
        return array_get($this->fees, $name, $default);
    }

    public function setFee($fee, $amount)
    {
        array_set($this->fees, $fee, $amount);

        return $this;
    }

    public function removeFee(string $name): self
    {
        if (array_has($this->fees, $name)) {
            unset($this->fees[$name]);
        }

        return $this;
    }

    public function hasCustomerFee() : bool
    {
        return (!empty($this->fees)  && isset($this->fees['customer_fee']));
    }

    public function getCustomerFee() : int
    {
        if ($this->hasCustomerFee()) {
            return $this->fees['customer_fee'];
        }

        return 0;
    }

    /**
     * Set customer product.
     *
     * @param CustomerProduct $customerProduct
     *
     * @return SubTransaction
     */
    public function setCustomerProduct(?CustomerProduct $customerProduct)
    {
        $this->customerProduct = $customerProduct;

        return $this;
    }

    /**
     * Get customer product.
     *
     * @return CustomerProduct
     */
    public function getCustomerProduct()
    {
        return $this->customerProduct;
    }

    public function getMemberProductCurrency(): Currency
    {
        return $this->getCustomerProduct()->getCurrency();
    }

    public function copyImmutableCustomerProductData() : SubTransaction
    {
        $cp = $this->getCustomerProduct();
        if ($cp instanceof CustomerProduct) {
            $customerProductUsername = $cp->getUsername();
            $this->setImmutableCustomerProductData($customerProductUsername);
        }


        return $this;
    }

    /**
     * @return String the customerProduct info during the time that the transaction was created
     */
    public function getImmutableCustomerProductData() : String
    {
        $immutableCustomerProductData = '';
        if (!empty($this->getDetail('immutableCustomerProductData.username'))) {
            $immutableCustomerProductData = $this->getDetail('immutableCustomerProductData.username');
        }
        return $immutableCustomerProductData;
    }

    public function setImmutableCustomerProductData($customerProductUsername) : SubTransaction
    {
        $this->setDetail('immutableCustomerProductData.username', $customerProductUsername);

        return $this;
    }

    /**
     * Set details.
     *
     * @param json $details
     *
     * @return Transaction
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details.
     *
     * @return json
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set specific detail.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Transaction
     */
    public function setDetail($key, $value)
    {
        array_set($this->details, $key, $value);

        return $this;
    }

    /**
     * Get specific detail.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getDetail($key, $default = null)
    {
        return array_get($this->details, $key, $default);
    }

    public function isBetSettled(): bool
    {
        return $this->getDetail('betSettled') === true;
    }

    public function getCategory()
    {
        $parent = $this->getParent();

        if ($parent->isDeposit()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DEPOSIT;
        } elseif ($parent->isWithdrawal()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_WITHDRAWAL;
        } elseif ($parent->isTransfer()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_TRANSFER;
        } elseif ($parent->isP2pTransfer()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_P2P_TRANSFER;
        } elseif ($parent->isDwl()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL;
        } elseif ($this->isBet()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_BET;
        } elseif ($parent->isBonus()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_BONUS;
        } elseif ($parent->isCommission()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_COMMISSION;
        }

        return $category;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'parent', 'details', 'type'];
    }

    public function getAssociationFields()
    {
        return ['customerProduct'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return sprintf('%s (%s)', $this->getParent()->getNumber(), $this->getCustomerProduct()->getUserName());
    }

    public function isAudit()
    {
        return true;
    }

    public function getDwlId(): ?int
    {
        return $this->dwlId;
    }

    public function getDwlTurnover(): ?float
    {
        return (string) $this->getDetail('dwl.turnover', '0');
    }

    public function getDwlWinLoss(): ?float
    {
        return $this->dwlWinLoss;
    }

    public function getAuditDetails(): array
    {
        return [
            'type' => $this->getType(),
            'parent' => $this->getParent(),
            'amount' => $this->getAmount(),
            'customerProduct' => $this->getCustomerProduct(),
        ];
    }

    public function getConvertedAmount(): string
    {
        return (string) $this->getDetail('convertedAmount', $this->getAmount());
    }

    public function revertCustomerBalance(): void
    {
         $this->getCustomerProduct()->revertBalanceFromSubtransaction($this);
    }

    public function setDWlInfo(array $info): void
    {
        foreach ($info as $key => $value) {
            $this->setDetail('dwl.' . $key, $value);
        }
    }

    public function setMemberRunningCommissionId(int $memberRunningCommissionId): void
    {
        $this->setDetail('running_commission.id', $memberRunningCommissionId);
    }

    public function getMemberRunningCommissionId(): ?int
    {
        return $this->getDetail('running_commission.id');
    }
}
