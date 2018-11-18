<?php

namespace DbBundle\Entity;

use AppBundle\ValueObject\Money;
use AppBundle\ValueObject\Number;
use DateTime;
use DbBundle\Entity\Interfaces\ActionInterface;
use DbBundle\Entity\Interfaces\AuditInterface;
use DbBundle\Entity\Interfaces\TimestampInterface;
use DbBundle\Entity\Interfaces\VersionInterface;
use DbBundle\Entity\Traits\ActionTrait;
use DbBundle\Entity\Traits\SoftDeleteTrait;
use DbBundle\Entity\Traits\TimestampTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;

/**
 * Transaction.
 */
class Transaction extends Entity implements ActionInterface, TimestampInterface, VersionInterface, AuditInterface
{
    use ActionTrait;
    use SoftDeleteTrait;
    use TimestampTrait;
    
    private const DETAIL_COMMISSION_CONVERTION_RATE = 'commission.convertion.rate';
    private const DETAIL_COMMISSION_PRODUCT_PERCENTAGE = 'commission.product.percentage';
    private const DETAIL_COMMISSION_COMPUTED = 'commission.computed';
    private const DETAIL_COMMISSION_CONVERTIONS = 'commission.convertions';

    const TRANSACTION_TYPE_DEPOSIT = 1;
    const TRANSACTION_TYPE_WITHDRAW = 2;
    const TRANSACTION_TYPE_TRANSFER = 3;
    const TRANSACTION_TYPE_BONUS = 4;
    const TRANSACTION_TYPE_P2P_TRANSFER = 5;
    const TRANSACTION_TYPE_DWL = 6;
    const TRANSACTION_TYPE_COMMISSION = 7;
    const TRANSACTION_TYPE_BET = 8;

    const TRANSACTION_STATUS_START = 1;
    const TRANSACTION_STATUS_END = 2;
    const TRANSACTION_STATUS_DECLINE = 3;
    const TRANSACTION_STATUS_ACKNOWLEDGE = 4;
    const TRANSACTION_STATUS_VOIDED = 'voided'; //This status is not included to settings status. since it was use in the same filtering as status. it might needed to add as parameter

    /**
     * @var string
     */
    private $number;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $amount = 0;

    /**
     * @var json
     */
    private $fees;

    /**
     * @var tinyint
     */
    private $type;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var tinyint
     */
    private $status;

    /**
     * @var tinyint
     */
    private $isVoided = false;

    /**
     * @var json
     */
    private $details;

    /**
     * @var ArrayCollection
     */
    private $subTransactions;

    /**
     * @var Customer
     */
    private $customer;

    /**
     * @var Gateway
     */
    private $gateway;

    private $paymentOption;

    private $paymentOptionOnTransaction;

    private $paymentOptionType;

    private $creator;

    private $toCustomer;
    private $dwlId;

    private $betId;
    private $betEventId;

    private $commissionComputedOriginal;

    private $immutablePaymentOptionData;

    public function __construct()
    {
        $this->setFees([]);
        $this->setStatus(self::TRANSACTION_STATUS_START);
        $this->subTransactions = new ArrayCollection();
        $this->setDetails([]);
    }

    /**
     * Set number.
     *
     * @param string $number
     *
     * @return Transaction
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set currency.
     *
     * @param string $currency
     *
     * @return Transaction
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency.
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

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
     * Set fees.
     *
     * @param json $fees
     *
     * @return Transaction
     */
    public function setFees($fees)
    {
        $this->fees = $fees;

        return $this;
    }

    /**
     * Get fees.
     *
     * @return json
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * Set specific fee.
     *
     * @param string          $key
     * @param int|float|float $fee
     *
     * @return Transaction
     */
    public function setFee($key, $fee)
    {
        array_set($this->fees, $key, $fee);

        return $this;
    }

    /**
     * Get specific fee.
     *
     * @param string          $key
     * @param int|float|float $default
     *
     * @return int|float|float
     */
    public function getFee($key, $default = 0)
    {
        return array_get($this->fees, $key, $default);
    }

    public function hasFee($key)
    {
        return array_has($this->fees, $key);
    }

    /**
     * Set type.
     *
     * @param tinyint $type
     *
     * @return Transaction
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return tinyint
     */
    public function getType()
    {
        return $this->type;
    }

    public function getTypeText()
    {
        return $this->getTypesText()[$this->getType()];
    }

    public function getTypesText(): array
    {
        return [
            static::TRANSACTION_TYPE_DEPOSIT => 'deposit',
            static::TRANSACTION_TYPE_WITHDRAW => 'withdraw',
            static::TRANSACTION_TYPE_TRANSFER => 'transfer',
            static::TRANSACTION_TYPE_P2P_TRANSFER => 'p2p_transfer',
            static::TRANSACTION_TYPE_BONUS => 'bonus',
            static::TRANSACTION_TYPE_DWL => 'dwl',
            static::TRANSACTION_TYPE_BET => 'bet',
            static::TRANSACTION_TYPE_COMMISSION => 'commission',
        ];
    }

    /**
     * Set date.
     *
     * @param DateTime $date
     *
     * @return Transaction
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set status.
     *
     * @param tinyint $status
     *
     * @return Transaction
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return tinyint
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set isVoided.
     *
     * @param tinyint $isVoided
     *
     * @return Transaction
     */
    public function setIsVoided($isVoided = false)
    {
        $this->isVoided = $isVoided;

        return $this;
    }

    /**
     * Get isVoided.
     *
     * @return tinyint
     */
    public function getIsVoided()
    {
        return $this->isVoided;
    }

    /**
     * Check if voided.
     *
     * @return type
     */
    public function isVoided()
    {
        return $this->getIsVoided();
    }

    public function isDeclined() : bool
    {
        return $this->getStatus() === Transaction::TRANSACTION_STATUS_DECLINE;
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

    /**
     * Get sub transaction.
     *
     * @return ArrayCollection
     */
    public function getSubTransactions()
    {
        return $this->subTransactions;
    }

    public function getFirstSubTransaction(): ?SubTransaction
    {
        if (empty($this->subTransactions)) {
            return null;
        }

        if ($this->subTransactions instanceof ArrayCollection) {
            return $this->subTransactions->get(0);
        }

        return $this->subTransactions[0];
    }

    /**
     * Add sub transaction.
     *
     * @param SubTransaction $sub
     *
     * @return Transaction
     */
    public function addSubTransaction(SubTransaction $sub)
    {
        $this->subTransactions->add($sub);
        $sub->setParent($this);

        return $this;
    }

    /**
     * Add sub transaction.
     *
     * @param SubTransaction $subs
     *
     * @return Transaction
     */
    public function setSubTransactions($subs = [])
    {
        foreach ($subs as $sub) {
            $this->subTransactions->add($sub);
            $sub->setParent($this);
        }

        return $this;
    }

    /**
     * Get Customer.
     *
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * Set Customer.
     *
     * @param Customer $customer
     *
     * @return Transaction
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get gateway.
     *
     * @return Gateway
     */
    public function getGateway()
    {
        return $this->gateway;
    }

    /**
     * Set gateway.
     *
     * @param Gateway $gateway
     *
     * @return Transaction
     */
    public function setGateway($gateway = null)
    {
        $this->gateway = $gateway;

        return $this;
    }

    public function getVersionColumn()
    {
        return 'updatedAt';
    }

    public function getVersionType()
    {
        return 'datetime';
    }


    public function getPaymentOption(): ?CustomerPaymentOption
    {
        return $this->paymentOption;
    }


    public function setPaymentOption($paymentOption)
    {
        $this->paymentOption = $paymentOption;

        return $this;
    }

    public function getPaymentOptionOnTransaction(): ?CustomerPaymentOption
    {
        return $this->paymentOptionOnTransaction;
    }


    public function setPaymentOptionOnTransaction($paymentOptionOnTransaction)
    {
        $this->paymentOptionOnTransaction = $paymentOptionOnTransaction;

        return $this;
    }

    // save data that should not be changed even if the records at the related tables change
    // this is a poor attempt to have historical data while auditlog is still not in place
    public function retainImmutableData()
    {
        if (!$this->isClosedForFurtherProcessing()) {
            $this->copyImmutableCustomerProductData();
            $this->setImmutableCustomerReferrer();
            if ($this->isWithdrawal() || $this->isDeposit()) {
                $this->setImmutablePaymentOptionData();
                $this->setImmutablePaymentOptionOnTransactionData();
            }
        }
    }

    public function retainImmutableDataForDWL(): void
    {
        if ($this->isDwl()) {
            $this->copyImmutableCustomerProductData();
        }
    }


    public function isClosedForFurtherProcessing() : bool
    {
        return ($this->hasEnded() || $this->isDeclined() || $this->isVoided());
    }

    public function setImmutableCustomerReferrer(): void
    {
        if ($this->getCustomer()->getAffiliate() instanceof Customer) {
            $this->setDetail('customer.referrer', [
                'id' => $this->getCustomer()->getAffiliate()->getId(),
                'f_name' => $this->getCustomer()->getAffiliate()->getFName(),
                'l_name' => $this->getCustomer()->getAffiliate()->getLName(),
                'user' => ['username' => $this->getCustomer()->getAffiliate()->getUser()->getUsername()],
                'full_name' => $this->getCustomer()->getAffiliate()->getFullName(),
            ]);
        }
    }

    public function getImmutableCustomerReferrer(): array
    {
        return $this->getDetail('customer.referrer');
    }

    public function hasImmutableCustomerReferrer(): bool
    {
        return $this->hasDetail('customer.referrer');
    }

    public function hasDetail(string $key): bool
    {
        return array_has($this->details, $key);
    }

    /**
     * to track the data of customer's payment option
     * (normally starting the time of transaction creation)
     */
    public function setImmutablePaymentOptionData()
    {
        $this->setDetail('paymentOption.email', array_get($this->paymentOption->getFields(),'email'));
        $this->setDetail('paymentOption.code', $this->paymentOption->getPaymentOption()->getCode());
        $this->setDetail('paymentOption.name', $this->paymentOption->getPaymentOption()->getName());
        if ($this->paymentOption->getPaymentOption()->isPaymentEcopayz()) {
            $this->setDetail('paymentOption.accountId', array_get($this->paymentOption->getFields(),'account_id'));
        }
    }

    /**
     * Set the payment option on transaction data
     * only on creation of transactions from member site
     */
    public function setImmutablePaymentOptionOnTransactionData()
    {
        if ($paymentOptionOnTransaction = $this->getPaymentOptionOnTransaction()) {
            $this->setDetail('paymentOptionOnTransaction.email', array_get($paymentOptionOnTransaction->getFields(),'email'));
            $this->setDetail('paymentOptionOnTransaction.code', $paymentOptionOnTransaction->getPaymentOption()->getCode());
            $this->setDetail('paymentOptionOnTransaction.name', $paymentOptionOnTransaction->getPaymentOption()->getName());
            if ($paymentOptionOnTransaction->getPaymentOption()->isPaymentEcopayz()) {
                $this->setDetail('paymentOptionOnTransaction.accountId', array_get($paymentOptionOnTransaction->getFields(),'account_id'));
            }
        }
    }

    /**
     * @return String the PaymentOption data info during the time that the transaction was created / requested
     * this may not be the actual payment option used to process/finalize the transaction
     */
    public function getImmutablePaymentOptionData() : String
    {
        $email = $this->getDetail('paymentOption.email');
        $paymentOptionCode = $this->getDetail('paymentOption.code');
        $paymentOption = $this->getPaymentOption();
        if ($paymentOption && $paymentOption->getPaymentOption()->isPaymentEcopayz()) {
            $accountId = $this->getDetail('paymentOption.accountId');

            return $paymentOptionCode. ' ('. $accountId .')';
        }

        return $paymentOptionCode. ' ('. $email .')';
    }

    public function getImmutablePaymentOptionOnTransactionData() : String
    {
        $email = $this->getDetail('paymentOptionOnTransaction.email');
        $paymentOptionCode = $this->getDetail('paymentOptionOnTransaction.code');
        $paymentOptionOnTransaction = $this->getPaymentOptionOnTransaction();
        if ($paymentOptionOnTransaction && $paymentOptionOnTransaction->getPaymentOption()->isPaymentEcopayz()) {
            $accountId = $this->getDetail('paymentOptionOnTransaction.accountId');

            return $paymentOptionCode. ' ('. $accountId .')';
        }

        return $paymentOptionCode. ' ('. $email .')';
    }

    public function copyImmutableCustomerProductData()
    {
        foreach ($this->getSubTransactions() as $subTransaction) {
            $subTransaction->copyImmutableCustomerProductData();
        }
    }

    public function setPaymentOptionType($paymentOptionType): self
    {
        $this->paymentOptionType = $paymentOptionType;

        return $this;
    }

    public function getPaymentOptionType(): ?PaymentOption
    {
        $this->autoSetPaymentOptionType();

        return $this->paymentOptionType;
    }

    public function getCustomerAmount()
    {        
        // zimi
        // return $this->getDetail('summary.customer_amount', $this->getDetail('summary.total_amount', 0));
        return $this->getAmount();
    }

    public function canAutoSetPaymentOptionType()
    {
        return $this->paymentOptionType === null && $this->paymentOption !== null;
    }

    public function autoSetPaymentOptionType(): self
    {
        if ($this->canAutoSetPaymentOptionType()) {
            $this->setPaymentOptionType($this->getPaymentOption()->getPaymentOption());
        }

        return $this;
    }
    public function isEnd(): bool
    {
        return $this->getStatus() === Transaction::TRANSACTION_STATUS_END;
    }

    public function hasEnded() : bool
    {
        return $this->isEnd() === true;
    }

    public function isNew(): bool
    {
        return $this->id === null;
    }

    public function isDeposit() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_DEPOSIT;
    }

    public function isWithdrawal() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_WITHDRAW;
    }

    public function isTransfer() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_TRANSFER;
    }

    public function isP2pTransfer() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_P2P_TRANSFER;
    }

    public function isBonus() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_BONUS;
    }

    public function isDwl() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_DWL;
    }

    public function isBet() : bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_BET;
    }

    public function isCommission(): bool
    {
        return $this->getType() === Transaction::TRANSACTION_TYPE_COMMISSION;
    }

    public function isInProgress() : bool
    {
        $statusesThatAreNotInProgress = [
            self::TRANSACTION_STATUS_START,
            self::TRANSACTION_STATUS_END,
            self::TRANSACTION_STATUS_DECLINE,
        ];

        return (!in_array($this->getStatus(), $statusesThatAreNotInProgress)) && !$this->isVoided();
    }

    public static function getTypeAsTexts(): array
    {
        return [
            self::TRANSACTION_TYPE_DEPOSIT => 'deposit',
            self::TRANSACTION_TYPE_WITHDRAW => 'withdraw',
            self::TRANSACTION_TYPE_TRANSFER => 'transfer',
            self::TRANSACTION_TYPE_P2P_TRANSFER => 'p2p transfer',
            self::TRANSACTION_TYPE_BET => 'bet',
            self::TRANSACTION_TYPE_DWL => 'dwl',
            self::TRANSACTION_TYPE_BONUS => 'bonus',
            self::TRANSACTION_TYPE_COMMISSION => 'commission',
        ];
    }

    public function getCreator() : ?User
    {
        return $this->creator;
    }

    public function setCreator(User $creator)
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCategory()
    {
        if ($this->isDeposit()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DEPOSIT;
        } elseif ($this->isWithdrawal()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_WITHDRAWAL;
        } elseif ($this->isTransfer()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_TRANSFER;
        } elseif ($this->isP2pTransfer()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_P2P_TRANSFER;
        } elseif ($this->isDwl()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_DWL;
        } elseif ($this->isBet()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_BET;
        } elseif ($this->isBonus()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_BONUS;
        } elseif ($this->isCommission()) {
            $category = AuditRevisionLog::CATEGORY_CUSTOMER_TRANSACTION_COMMISSION;
        }

        return $category;
    }

    public function getIgnoreFields()
    {
        return ['createdBy', 'createdAt', 'updatedBy', 'updatedAt', 'paymentOptionType', 'creator'];
    }

    public function getAssociationFields()
    {
        return ['currency', 'customer', 'gateway', 'paymentOption'];
    }

    public function getIdentifier()
    {
        return $this->getId();
    }

    public function getLabel()
    {
        return $this->getNumber();
    }

    public function isAudit()
    {
        return true;
    }

    public function getToCustomer(): ?int
    {
        return $this->toCustomer;
    }

    public function getDwlId(): ?int
    {
        return $this->dwlId;
    }

    public function getBetId(): ?int
    {
        return $this->betId;
    }

    public function getBetEventId(): ?int
    {
        return $this->betEventId;
    }

    public function getCommissionComputedOriginal(): ?float
    {
        return $this->commissionComputedOriginal;
    }

    public function setGatewayComputedAmount($amount): Transaction
    {
        $this->setDetail('paymentGateway.computed_amount', $amount);

        return $this;
    }

    public function getAuditDetails(): array
    {
        return [
            'number' => $this->getNumber(),
            'amount' => $this->getAmount(),
            'type' => $this->getType(),
            'date' => $this->getDate(),
            'status' => $this->getStatus(),
            'isVoided' => $this->getIsVoided(),
            'toCustomer' => $this->getToCustomer(),
        ];
    }

    public function getVoidingReason(): ?string
    {
        return $this->getDetail('reasonToVoidOrDecline');
    }

    public function setReasonToVoidOrDecline($reason)
    {
        $this->setDetail('reasonToVoidOrDecline', $reason);

        return $this;
    }

    public function setImmutablePaymentGatewayFormula(): void
    {
        if ($this->getGateway() instanceof Gateway) {
            $method = $this->getGateway()->getMethodForTransactionType($this->getTypeText());
            $this->setDetail('paymentGateway.equation', $method['equation']);
            $this->setDetail('paymentGateway.variables', $method['varialbles'] ?? []);
        }
    }

    public function decline(): self
    {
        $this->setStatus(self::TRANSACTION_STATUS_DECLINE);

        return $this;
    }

    public function void(): self
    {
        $this->setIsVoided(true);

        return $this;
    }

    public function getMemberRunningCommissionId(): int
    {
        return (int) $this->getDetail('commission.running_commission_id');
    }

    public function hasMemberRunningCommissionId(): bool
    {
        return $this->hasDetail('commission.running_commission_id');
    }

    public function setMemberRunningCommissionId(int $memberRunningCommissionid): void
    {
        $this->setDetail('commission.running_commission_id', $memberRunningCommissionid);
    }
    
    public function computeCommission(CurrencyRate $convertionRate, string $productCommissionPercentage): void
    {
        $this->setCommissionConvertionRate($convertionRate);
        $this->setProductCommissionPercentage($productCommissionPercentage);
        
        $turnover = $this->getFirstSubTransaction()->getDwlTurnover();
        $commissionPercentageAsDecimal = Number::div($productCommissionPercentage, 100);
        
        $computed = [];
        $commission = new Money(
            $convertionRate->getSourceCurrency(),
            Number::mul($turnover, $commissionPercentageAsDecimal)->toString()
        );
        $computed[$commission->getCurrencyCode()] = $commission->getAmount();
        $computed['original'] = $commission->getAmount();

        if (!$convertionRate->getSourceCurrency()->isEqualTo($convertionRate->getDestinationCurrency())) {
            $convertedCommission = $commission->convertToCurrency(
                $convertionRate->getDestinationCurrency(), 
                $convertionRate->getSourceRate(),
                $convertionRate->getDestinationRate()
            );
            $computed[$convertedCommission->getCurrencyCode()] = $convertedCommission->getAmount();
            $computed['original'] = $convertedCommission->getAmount();
        }
        
        $this->setDetail(self::DETAIL_COMMISSION_COMPUTED, $computed);
    }
    
    public function getCommissionForCurrency(string $code): string
    {
        return $this->getDetail(self::DETAIL_COMMISSION_COMPUTED . '.' . $code);
    }
    
    public function getComputedAmount(): array
    {
        return $this->getDetail(self::DETAIL_COMMISSION_COMPUTED, []);
    }
    
    public function setCommissionConvertions(array $convertions): void
    {
        $this->setDetail(self::DETAIL_COMMISSION_CONVERTIONS, $convertions);
    }
    
    public function getCommissionConvertions(): array
    {
        return $this->getDetail(self::DETAIL_COMMISSION_CONVERTIONS, []);
    }
    
    private function setCommissionConvertionRate(CurrencyRate $rate): void
    {
        if (!$this->getCurrency()->isEqualTo($rate->getSourceCurrency())) {
            throw new Exception(sprintf(
                'The source currency must be equal to transaction currency, Source Currency %s given',
                $rate->getSourceCurrency()->getCode()
            ));
        }
        $this->setDetail(self::DETAIL_COMMISSION_CONVERTION_RATE, [
            'source' => [
                'currency' => $rate->getSourceCurrency()->getCode(),
                'rate' => $rate->getSourceRate(),
            ],
            'destination' => [
                'currency' => $rate->getDestinationCurrency()->getCode(),
                'rate' => $rate->getDestinationRate(),
            ],
        ]);
    }
    
    private function setProductCommissionPercentage(string $percentage): void
    {
        $this->setDetail(self::DETAIL_COMMISSION_PRODUCT_PERCENTAGE, $percentage);
    }
}
