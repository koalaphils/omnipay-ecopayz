<?php

namespace DbBundle\Entity;

use AppBundle\ValueObject\Number;
use DbBundle\Entity\Interfaces\AuditInterface;

/**
 * SubTransaction.
 */
class SubTransaction extends Entity implements AuditInterface
{
    public const DETAIL_BITCOIN_REQUESTED_BTC = 'bitcoin.requested_btc';
    
    private const DETAILS_DWL_ID = 'dwl.id';
    private const DETAILS_DWL_GROSSCOMMISSION = 'dwl.gross';
    private const DETAILS_DWL_WINLOSS = 'dwl.winLoss';
    private const DETAILS_DWL_CUSTOMER_BALANCE = 'dwl.customer.balance';
    private const DETAILS_DWL_TURNOVER = 'dwl.turnover';
    private const DETAILS_DWL_COMMISSION = 'dwl.commission'; 
    private const DETAILS_BITCOIN_REQUESTED_AMOUNT = 'bitcoin.requested_btc';

    private const DETAILS_HAS_TRANSACTED_WITH_PIWI_MEMBER_WALLET = 'integration.has_transacted_with_piwi_member_wallet';
    private const DETAILS_HAS_FAILED_PROCESSING_INTEGRATION = 'integration.failed_processing';

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

    private $dwlTurnover;

    private $dwlWinLoss;

    private $immutableUsername;

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

    public function isVoided(): bool
    {
        return $this->getParent()->isVoided();
    }

    public function isDebitAdjustment(): bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_DEBIT_ADJUSTMENT;
    }

    public function isCreditAdjustment(): bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_CREDIT_ADJUSTMENT;
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
        return $this->immutableUsername ?? '';
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

    public function hasDetail(string $key): bool
    {
        return array_has($this->details, $key);
    }

    public function getCategory()
    {
        $parent = $this->getParent();
        
        // zimi
        if ($parent === null) {
            return AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DEPOSIT;
        }

        if ($parent->isDeposit()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DEPOSIT;
        } elseif ($parent->isWithdrawal()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_WITHDRAWAL;
        } elseif ($parent->isTransfer()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_TRANSFER;
        } elseif ($parent->isP2pTransfer()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_P2P_TRANSFER;
        } elseif ($parent->isBonus()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_BONUS;
        } elseif ($parent->isCommission()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_COMMISSION;
         } elseif ($parent->isRevenueShare()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_REVENUE_SHARE;
        } elseif ($parent->isDebitAdjustment()) {
            $category = AuditRevisionLog::CATEGORY_MEMBER_TRANSACTION_DEBIT_ADJUSTMENT;
        } elseif ($parent->isCreditAdjustment()) {
            $category = AuditRevisionLog::CATEGORY_MEMBER_TRANSACTION_CREDIT_ADJUSTMENT;
        }

        return $category;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'parent'];
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
        // zimi
        if ($this->getParent() === null) {
            return '';
        }

        return sprintf('%s (%s)', $this->getParent()->getNumber(), $this->getCustomerProduct()->getUserName());
    }

    public function isAudit()
    {
        return true;
    }

    public function getDwlTurnover(): ?string
    {
        return (string) $this->getDetail('dwl.turnover', '0');
    }

    public function getDwlWinLoss(): ?string
    {
        return (string) $this->dwlWinLoss;
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

    public function setDwlGrossCommission(string $grossCommission): self
    {
        $this->setDetail(self::DETAILS_DWL_GROSSCOMMISSION, $grossCommission);

        return $this;
    }

    public function setHasTransactedWithPiwiWalletMember(bool $bool): void
    {
        $this->setDetail(self::DETAILS_HAS_TRANSACTED_WITH_PIWI_MEMBER_WALLET, $bool);
    }

    public function getHasTransactedWithPiwiWalletMember(): bool
    {
        return $this->getDetail(self::DETAILS_HAS_TRANSACTED_WITH_PIWI_MEMBER_WALLET, false);
    }

    public function setFailedProcessingWithIntegration(bool $bool): void
    {
        $this->setDetail(self::DETAILS_HAS_FAILED_PROCESSING_INTEGRATION, $bool);
    }

    public function getFailedProcessingWithIntegration(): bool
    {
        return $this->getDetail(self::DETAILS_HAS_FAILED_PROCESSING_INTEGRATION, false);
    }

    public function getHasTransactedToIntegration(): bool
    {
        return $this->getDetail(self::DETAILS_HAS_FAILED_PROCESSING_INTEGRATION, null) !== null;
    }

    /*
    public function getDwlGrossCommission(): string
    {
        return $this->getDetail(self::DETAILS_DWL_GROSSCOMMISSION, '0');
    }

    public function setDwlWinLoss(string $commission): self
    {
        $this->setDetail(self::DETAILS_DWL_WINLOSS, $commission);

        return $this;
    }

    public function setDwlCustomerBalance(string $customerBalance): self
    {
        $this->setDetail(self::DETAILS_DWL_CUSTOMER_BALANCE, $customerBalance);

        return $this;
    }

    public function getDwlCustomerBalance(): string
    {
        return $this->getDetail(self::DETAILS_DWL_CUSTOMER_BALANCE);
    }

    public function setDwlTurnover(string $turnover): self
    {
        $this->setDetail(self::DETAILS_DWL_TURNOVER, $turnover);

        return $this;
    }

    public function setDwlCommission(string $commission): self
    {
        $this->setDetail(self::DETAILS_DWL_COMMISSION, $commission);

        return $this;
    }

    public function setDWLExcludeInList(bool $exclude): self
    {
        if ($exclude) {
            $this->setDetail(self::DETAILS_DWL_EXCLUDE_IN_LIST, 1);
        } else {
            $this->setDetail(self::DETAILS_DWL_EXCLUDE_IN_LIST, 0);
        }

        return $this;
    }

    public function isDwlExcludeInList(): bool
    {
        return $this->getDetail(self::DETAILS_DWL_EXCLUDE_IN_LIST, 0) === 1;
    }*/
}